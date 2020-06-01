<?php

namespace NsUtil\Integracao;

abstract class Adapter {

    protected $token, $endpoint, $appkey;

    public function __construct($endpoint, $appkey) {
        $this->endpoint = $endpoint;
        $this->appkey = $appkey;
        $this->login();
    }

    private function login() {
        $ret = $this->call('login/enter', [], 'GET', ['App-Key' => $this->appkey]);
        if ($ret->status === 200 && $ret->error === false && $ret->content->token) {
            $this->token = $ret->content->token;
        } else {
            die('Não foi possível efetuar login com as credenciais informadas');
        }
    }

    public function call($recurso, $params = [], $method = 'POST', $header = []) {
        $url = $this->endpoint . '/' . $recurso;

        $client = new \GuzzleHttp\Client();
        $res = $client->request($method, $url, [
            'verify' => false,
            'headers' => array_merge(['Token' => $this->token], $header),
            'json' => $params
        ]);

        $body = \json_decode($res->getBody());


        $out = new \stdClass();
        $out->status = $res->getStatusCode();
        $out->error = (boolean) $body->error;
        $out->content = $body->content;

        return $out;
    }
    
    
}
