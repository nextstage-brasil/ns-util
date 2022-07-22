<?php

namespace NsUtil\Integracao\Pagarme;

class User extends AbstractPagarme {

    private $user;
    static $status = [
        'registration' => 'estágio no qual o recebedor já passou pelo pré-credenciamento, e está com o auto credenciamento pendente. Aqui ele pode transacionar, mas não pode sacar o valor.',
        'affiliation' => 'estágio no qual o recebedor já passou pelo auto credenciamento, e está esperando a afiliação por parte do Pagar.me. Ele continua podendo transacionar, mas sem sacar o valor.',
        'active' => 'estágio no qual já passou por todos os estágios do credenciamento, e já pode sacar os valores não bloqueados de sua conta de pagamento.',
        'refused' => 'estágio no qual o recebedor foi recusado no pré-credenciamento ou na afiliação. Neste ponto, avisamos o marketplace, e depois de um período estornamos as vendas feitas.',
        'suspended' => 'estágio no qual o recebedor não se auto credenciou após um período de dias, ou teve pendências no estágio de afiliação. Tem o transacional e saques travados, mas as transações ainda não serão estornadas. Pode voltar para o fluxo de credenciamento/afiliação.',
        'blocked' => 'estágio no qual após ativo, mostrou comportamento suspeito. Tem o transacional e saques travados, mas as transações ainda não serão estornadas. Pode voltar para ativo.',
        'inactive' => 'estágio no qual após ser monitorado, mostrou comportamento suspeito, e comprovamos algo de errado. Quando ele atinge esse estágio, não é possível voltar para ativo.',
    ];

    public function __construct($idPagarme) {
        parent::setPagarme();
        $this->setUser($idPagarme);
    }

    private function setUser($idPagarme) {
        $this->user = $this->pagarme->recipients()->get(['id' => $idPagarme]);
    }

    public function getDados() {
        return $this->user;
    }

    public function has() {
        return isset($this->user->id);
    }

    public function setKey($chave, $valor) {
        if ($chave === 'id') {
            return false;
        }
        $this->user->$chave = $valor;
    }

    public function getKey($chave) {
        return $this->user->$chave;
    }

    public function getStatus() {
        return [
            'status' => $this->user->status,
            'descricao' => self::$status[$this->user->status]
        ];
    }

    function getAccountBank() {
        $ent = new \syncpay\Integracao\Pagarme\Entities();
        return (object) array_merge($ent->getBankAccount('', '', '', '', '', ''), (array) $this->getKey('bank_account'));
    }

    public function isActive() {
        return $this->user->status === 'active';
    }

    public function save($params = []) {
        if (!$this->has()) {
            throw new \Exception('Não posso salvar usuário pois ele não existe ainda. Use Adicionar');
        }
        $params['id'] = $this->user->id;

        $user = $this->pagarme->recipients()->update($params);
        if (!$user->id) {
            throw new Exception("Erro ao atualizar usuário: " . $user->error);
        }
        $this->user = $user;
    }

    public function accountList($cpf = null) {
        $cpf = $cpf ? $cpf : $this->user->bank_account->document_number;
        return parent::accountList(['document_number' => $cpf]);
    }

    /**
     * Verifica se uma conta já existe para o CPF. Caso exista, irá retornar o object account 
     * @param array $BankAccount
     * @return boolean
     */
    public function accountHas(array $BankAccount) {
        $out = (object) $BankAccount;
        $hash = md5((string)$out->bank_code . $out->agencia . $out->conta . $out->document_number . $out->type);
        $list = $this->accountList($out->document_number);
        foreach ($list as $out) {
            $verify = md5((string)$out->bank_code . $out->agencia . $out->conta . $out->document_number . $out->type);
            if ($hash === $verify) {
                return $out;
            }
        }
        return false;
    }

    public function accountUpdate(array $BankAccount) {

        try {
            // somente do mesmo CPF é aceito
            if ($BankAccount['document_number'] !== $this->user->bank_account->document_number) {
                throw new \Exception("O número do CPF ou CNPJ informado (" . $this->user->bank_account->document_number . ") precisa ser o mesmo da conta de criação do usuário (" . $BankAccount['document_number'] . ")");
            }

            // verificar se a conta informada já existe
            $account = $this->accountHas($BankAccount);
            if (!$account) {
                // Criar account
                $account = $this->pagarme->bankAccounts()->create($BankAccount);
                if (!$account->id) {
                    throw new Exception("Erro ao adicionar conta: " . $account->error);
                }
            }

            // Atualizar usuario somente se alterar o ID da conta atual
            if ($this->user->bank_account->id !== $account->id) {
                $this->save(['bank_account_id' => $account->id]);
            }
        } catch (Exception $exc) {
            return ['error' => $exc->getMessage()];
        }
    }

    public function getSaldo() {
        $saldo = $this->pagarme->recipients()->getBalance(['recipient_id' => $this->user->id]);
        $out = [
            'pendente' => $saldo->waiting_funds->amount / 100,
            'transferido' => $saldo->transferred->amount / 100,
            'disponivel' => $saldo->available->amount / 100
        ];
        return (object) $out;
    }

    public function getExtrato() {
        $json = $this->pagarme->payables()->getList(['recipient_id' => $this->user->id, 'status' => $status]);
        //\NsUtil\Helper::array2csv(json_decode(json_encode($json), true), __DIR__ . '/../../../app/recebiveis.csv');

        return $this->pagarme->recipients()->listBalanceOperation(['recipient_id' => $this->user->id]);
    }

    /**
     * Ira retornar os recebíveis conforme status
     * @param type $status paid or waiting_funds
     * @return type
     */
    public function getPayables($status = 'waiting_funds') {
        $params = ['recipient_id' => $this->user->id];
        if ($status !== false) {
            $params['status'] = $status;
        }
        return $this->pagarme->payables()->getList($params);
    }

    /**
     * 
     * @param int $amount Valor em centavos a ser transferido
     * @return type
     */
    public function transferAdd(int $amount) {
        if ($amount === 0) {
            return ['error' => 'Favor definir um valor para saque'];
        }
        $params = ['recipient_id' => $this->user->id, 'amount' => $amount];
        try {
            $out = $this->pagarme->transfers()->create($params);
        } catch (\PagarMe\Exceptions\PagarMeException $exc) {
            $out['error'] = parent::exceptionToObject($exc);
        } catch (\Exception $exc) {
            $out['error'] = parent::exceptionToObject($exc);
        }

        return $out;
    }

}
