<?php

namespace NsUtil\Integracao;

use NsUtil\Integracao\Adapter;

class Syncpay extends Adapter {

    private $header;

    public function __construct($endpoint, $appkey) {
        parent::__construct($endpoint, $appkey, false);
        $this->header = ['App-Key' => $appkey];
        $this->ignoreLogin(180);
    }

    public function call($recurso, $params = array()) {
        return parent::_call($recurso, $params, 'POST', $this->header);
    }

    public function getOperadoras() {
        return $this->call('empresaOperadora/list');
    }

    /**
     * Criara uma nova empresa no Syncpay retornando a AppKey
     * @param type $nome
     * @param type $email
     * @param type $cnpj
     * @param type $licencaPermisso
     */
    public function novoCliente($nome, $email, $cnpj, $licencaPermisso) {
        $ret = $this->call('empresa/save', [
            'nomeEmpresa' => $nome,
            'emailEmpresa' => $email,
            'extrasEmpresa' => json_encode(["permisso" => $licencaPermisso, "cnpj" => $cnpj])
                ]);
        if (strlen((string)$ret->content->AppKey) > 0) {
            return ['error' => false, 'appkey' => $ret->content->AppKey];
        } else {
            return ['error' => $ret->error];
        }
    }

}
