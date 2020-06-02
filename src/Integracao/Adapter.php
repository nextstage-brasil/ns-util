<?php

namespace NsUtil\Integracao;

abstract class Adapter {

    protected $token, $endpoint, $appkey, $showLogs, $sessionName;

    public function __construct($endpoint, $appkey, $autologin = false) {
        $this->endpoint = $endpoint;
        $this->appkey = $appkey;
        if (!$this->sessionName) {
            $this->sessionName = md5($endpoint . $appkey);
        }
        $_SESSION[$this->sessionName] = 0;
        if ($autologin) {
            $this->login();
        }
    }

    private function login() {
        // se validade for menor que agora, faz login
        if ((int) $_SESSION[$this->sessionName] < time()) {
            $_SESSION[$this->sessionName] = time() + (1000 * 3); // pra evitar auto loopíng ao usar call
            $ret = $this->call('login/enter', [], 'GET', ['App-Key' => $this->appkey]);
            $_SESSION[$this->sessionName] = 0;
            $this->printLogsOnScreen('Ret-Login: ' . var_export($ret, true));
            if ($ret->status === 200 && $ret->error === false && $ret->content->token) {
                $this->token = $ret->content->token;
            } else {
                die('Não foi possível efetuar login em "' . $this->endpoint . '" com as credenciais informadas');
            }
            $_SESSION[$this->sessionName] = time() + (58 * $ret->content->expire); // duração do token, para não ficar fazendo login toda hora
        }
    }

    public function call($recurso, $params = [], $method = 'POST', $header = []) {
        $this->login();
        try {
            $url = $this->endpoint . '/' . $recurso;
            $this->printLogsOnScreen('URL: ' . $url);

            $client = new \GuzzleHttp\Client();
            $res = $client->request($method, $url, [
                'verify' => false,
                'headers' => array_merge(['Token' => $this->token], $header),
                'json' => $params
            ]);
            $this->printLogsOnScreen('Body: ' . json_encode(json_decode($res->getBody())));
            $body = \json_decode($res->getBody());


            $out = new \stdClass();
            $out->status = $res->getStatusCode();
            $out->error = (boolean) $body->error;
            $out->content = $body->content;
            $out->content->token = (($body->token) ? $body->token : false);
            $out->errorText = (($body->content->error) ? $body->content->error : $body->error);
            return $out;
        } catch (Exception $exc) {
            $out = new \stdClass();
            $out->status = $res->getStatusCode();
            $out->error = (boolean) true;
            $out->content = $exc->getMessage();
        }
    }

    protected function printLogsOnScreen($text) {
        if ($this->showLogs) {
            echo "<hr>printLogAdapterIntegracao: <br/><br/>: ";
            echo $text . "<hr>";
        }
    }

}
