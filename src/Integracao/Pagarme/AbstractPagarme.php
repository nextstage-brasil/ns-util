<?php

namespace NsUtil\Integracao\Pagarme;

abstract class AbstractPagarme {

    protected $pagarme;
    public static $apikey = false;

    protected function setPagarme() {
        self::$apikey = \Config::getData('integracao', 'pagarme')['apikey'];
        if (!self::$apikey) {
            throw new \Exception('APIKey do Pagarme não definida');
        }
        $this->pagarme = new \PagarMe\Client(self::$apikey);
    }

    public function getLinkToJavascriptForDirectPayemnt() {
        return '<script src="https://assets.pagar.me/pagarme-js/4.5/pagarme.min.js"></script>';
    }

    public static function exceptionToObject(\Exception $exc) {
        //ERROR TYPE: not_found. PARAMETER: . MESSAGE: Recipient não encontrado
        $string = $exc->getMessage();
        $out = [];
        $t1 = explode("PARAMETER:", $string);
        if (!isset($t1[1])) {
            return $string;
        }
        $out['type'] = trim(str_replace('ERROR_TYPE:', '', $tl[0]));

        $t2 = explode('MESSAGE:', $t1[1]);
        $out['parameter'] = Config::getDicionario(str_replace('.', '', trim($t2[0])));
        $out['message'] = Config::getDicionario(trim($t2[1]));

        return $out['parameter'] . ((strlen((string)$out['message']) > 0) ? ': ' . $out['message'] : '');
    }

    public function accountList($params = []) {
        return $this->pagarme->bankAccounts()->getList($params);
    }

    public function recebedorAdd(array $BankAccount) {
        $recipient = $this->pagarme->recipients()->create([
            'anticipatable_volume_percentage' => '0',
            'automatic_anticipation_enabled' => 'false',
            'bank_account' => $BankAccount,
            'transfer_day' => '0',
            'transfer_enabled' => 'false',
            'transfer_interval' => 'daily'
        ]);
        return $recipient;
    }

    public function recebedorGet() {
        return $this->pagarme->recipients()->getList();
    }

    public function listar() {
        $filters = [];
        return $this->pagarme->recipients()->getList($filters);
    }

    /**
     * Valida se o postback recebido é valido
     * @return \stdClass
     */
    public function validaPostback() {
        $requestBody = file_get_contents("php://input");
        $signature = \Config::getData('headers')['X-Hub-Signature'];
        if ($this->pagarme->postbacks()->validate($requestBody, $signature)) {
            return true;
        } else {
            \Log::error('Invalid postback receive', ['request' => $requestBody, 'dados' => \Config::getData('dados'), 'headers' => \Config::getData('headers')]);
            return false;
        }
    }

}
