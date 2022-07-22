<?php

namespace NsUtil\Integracao\Pagarme;

use NsUtil\Format;

class Entities {

    private $f;

    public function __construct() {
        $this->f = new Format();
    }

    /**
     * Retorna um array do tipo Customer
     * @param type $id
     * @param type $nome
     * @param type $cpf
     * @param type $email
     * @param type $fone1
     * @param type $fone2
     * @param type $country
     * @param type $type
     * @return array
     */
    public function getCustomer($id, $nome, $cpf, $email, $fone1, $fone2 = false, $country = 'br', $type = 'individual'): array {
        $fones[] = '+55' . $this->f->setString($fone1)->parseInt();
        if ($fone2) {
            $fones[] = '+55' . $this->f->setString($fone2)->parseInt();
        }
        return [
            'external_id' => (string) $id,
            'name' => $nome,
            'type' => $type,
            'country' => $country,
            'documents' => [
                [
                    'type' => 'cpf',
                    'number' => $cpf
                ]
            ],
            'phone_numbers' => $fones,
            'email' => $email
        ];
    }

    /**
     * 
     * @param type $rua
     * @param type $numero
     * @param type $estado com Duas letras
     * @param type $cidade
     * @param type $bairro
     * @param type $cep
     * @param type $country
     * @return type
     */
    public function getAddress($rua, $numero, $estado, $cidade, $bairro, $cep, $country = 'br'): array {
        return [
            'country' => 'br',
            'street' => (string) $rua,
            'street_number' => (string) $numero,
            'state' => (string) $estado,
            'city' => (string) $cidade,
            'neighborhood' => (string) $bairro,
            'zipcode' => (string) $this->f->setString($cep)->parseInt()
        ];
    }

    /**
     * Retornar um array do tipo Billing
     * @param type $nomePagador
     * @param array $Address Array obtido em Address
     * @return type
     */
    public function getBilling($nomePagador, array $Address): array {
        return [
            'name' => $nomePagador,
            'address' => $Address
        ];
    }

    /**
     * 
     * @param string $nomeRecebedor
     * @param string $fee
     * @param string $dataEntrega
     * @param array $Address
     * @param type $expedido
     * @return array
     */
    public function getShipping(string $nomeRecebedor, string $fee, string $dataEntrega, array $Address, $expedido = false): array {
        return [
            'name' => $nomeRecebedor,
            'fee' => $fee,
            'delivery_date' => $this->f->setString($dataEntrega)->date(),
            'expedited' => $expedido,
            'address' => $Address
        ];
    }

    /**
     * 
     * @param string $idItem
     * @param string $title
     * @param int $valorUnitario
     * @param int $quantidade
     * @param bool $tangible
     * @return array
     */
    public function getItem(string $idItem, string $title, $valorUnitario, int $quantidade, bool $tangible = false): array {
        return [
            'id' => (string) $idItem,
            'title' => $title,
            'unit_price' => $this->f->setString($valorUnitario)->parseInt(),
            'quantity' => $quantidade,
            'tangible' => $tangible
        ];
    }

    /**
     * 
     * @param string $idRecebedor
     * @param int $amount
     * @param bool $charge_processing_fee
     * @param bool $liable
     * @return array
     */
    public function getSplitRule(string $idRecebedor, $amount, bool $charge_processing_fee = true, bool $liable = true): array {
        return [
            'amount' => (string) $this->f->setString($amount)->parseInt(),
            'recipient_id' => $idRecebedor,
            'charge_processing_fee' => $charge_processing_fee,
            'liable' => $liable,
        ];
    }

    /**
     * Comparação do status de pedido do sistema com os nomes de status de transacao do pagarme
     * @return type
     */
    public function getStatusDePara() {
        return [
            'processing' => 1,
            'waiting_payment' => 1,
            'authorized' => 50,
            'paid' => 50,
            'refused' => 11,
            'pending_refund' => 14,
            'refunded' => 14
        ];
    }

    public function getBankAccount($banco, $agencia_com_digito, $conta_com_digito, $cpfDonoConta, $nomeDonoConta, $type = 'conta_corrente'): array {
        // agencia
        $agencia = explode('-', $agencia_com_digito);


        // conta
        $conta = explode('-', $conta_com_digito);
        if (!isset($conta[1])) {
            $conta[1] = substr($conta_com_digito, -1);
            $conta[0] = substr($conta_com_digito, 0, (strlen((string)$conta_com_digito) - 1));
        }
        $out = [
            'bank_code' => $banco,
            'agencia' => $agencia[0],
            'conta' => $conta[0],
            'conta_dv' => $conta[1],
            'type' => $type,
            'document_number' => $cpfDonoConta,
            'legal_name' => mb_strtoupper($nomeDonoConta)
        ];
        if ($agencia[1]) {
            $out['agencia_dv'] = (int) $agencia[1];
        }

        \Log::logTxt('banco', $conta_com_digito);
        \Log::logTxt('banco', $out);
        \Log::logTxt('banco', $conta);

        return $out;
    }

    public function getCostCompany($creditCardFixed, $debitCardFixed, $boletoFixed, $pixFixed, array $mdrs) {
        return [
            // Taxas fixas, valores em reais e em milhares
            'fixed' => [
                'credit_card' => $creditCardFixed,
                'debit_card' => $debitCardFixed,
                'boleto' => $boletoFixed,
                'pix' => $pixFixed,
            ],
            // Taxas de bandeiras e formas de cobrança. Valoresd em percentual e numeric
            'mdr' => $mdrs
        ];
    }

}
