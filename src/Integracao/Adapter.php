<?php

namespace NsUtil\Integracao;

use Exception;
use NsUtil\Helper;

abstract class Adapter {

    protected $token, $endpoint, $appkey, $showLogs, $sessionName;
    private $atualLoginTime;

    public function __construct($endpoint, $appkey, $autologin = false) {
        $this->endpoint = $endpoint;
        $this->appkey = $appkey;
        if (!$this->sessionName) {
            $this->sessionName = md5((string)$endpoint . $appkey);
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
                throw new \Exception('IntegracaoAdapterLogin: Não foi possível efetuar login em "' . $this->endpoint . '" com as credenciais informadas');
            }
            $_SESSION[$this->sessionName] = time() + (58 * $ret->content->expire); // duração do token, para não ficar fazendo login toda hora
        }
    }

    /**
     * Ignora por uma unica vez a verificação de login
     */
    public function ignoreLogin(int $segundos = 3) {
        $this->atualLoginTime = $_SESSION[$this->sessionName];
        $_SESSION[$this->sessionName] = time() + 3 * $segundos;
    }

    public function call($recurso, $params = [], $method = 'POST', $header = []) {
        return $this->_call($recurso, $params, $method, $header);
    }


    /**
     * Executa uma chamada a API conforme recurso. Ira verificar login valido antes de executar
     * @param string $recurso
     * @param array $params
     * @param string $method
     * @param array $header
     * @return \stdClass
     */
    public function _call($recurso, $params = [], $method = 'POST', $header = []) {
        $this->login();
        try {
            $url = $this->endpoint . '/' . $recurso;
            $this->printLogsOnScreen('URL: ' . $url);
            $headerNew = ['Token:' . $this->token];
            foreach ($header as $key => $val) {
                $headerNew[] = "$key:$val";
            }
            $ssl = false;
            $timeout = 30;
            $out = Helper::curlCall($url, $params, $method, $headerNew, $ssl, $timeout);
            $body = json_decode($out->content);
            $out->error = $out->error > 0;
            $out->content->token = $body->token ?? $body->content->token ?? null;
            $out->errorText = $body->content->error ?? $body->error ?? '';

            return $out;
        } catch (Exception $exc) {
            $out->error = (bool) true;
            $out->content = $exc->getMessage();
        } finally {
            if ($this->atualLoginTime) {
                $_SESSION[$this->sessionName] = $this->atualLoginTime;
                $this->atualLoginTime = false;
            }
        }
    }

    // /**
    //  * Executa uma chamada a API conforme recurso. Ira verificar login valido antes de executar
    //  * @param type $recurso
    //  * @param type $params
    //  * @param type $method
    //  * @param type $header
    //  * @return \stdClass
    //  */
    // public function _call($recurso, $params = [], $method = 'POST', $header = []) {
    //     $this->login();
    //     try {
    //         $url = $this->endpoint . '/' . $recurso;
    //         $this->printLogsOnScreen('URL: ' . $url);

    //         $client = new \GuzzleHttp\Client();
    //         $res = $client->request($method, $url, [
    //             'verify' => false,
    //             'headers' => array_merge(['Token' => $this->token], $header),
    //             'json' => $params
    //         ]);
    //         $this->printLogsOnScreen('Body: ' . json_encode(json_decode($res->getBody())));
    //         $body = \json_decode($res->getBody());


    //         $out = new \stdClass();
    //         $out->status = $res->getStatusCode();
    //         $out->error = (bool) $body->error;
    //         $out->content = $body->content;
    //         $out->content->token = (($body->token) ? $body->token : false);
    //         $out->errorText = (($body->content->error) ? $body->content->error : $body->error);
    //         return $out;
    //     } catch (Exception $exc) {
    //         $out = new \stdClass();
    //         $out->status = $res->getStatusCode();
    //         $out->error = (bool) true;
    //         $out->content = $exc->getMessage();
    //     } finally {
    //         if ($this->atualLoginTime) {
    //             $_SESSION[$this->sessionName] = $this->atualLoginTime;
    //             $this->atualLoginTime = false;
    //         }
    //     }
    // }

    protected function printLogsOnScreen($text) {
        if ($this->showLogs) {
            echo "<hr>printLogAdapterIntegracao: <br/><br/>: ";
            echo $text . "<hr>";
        }
    }
}
