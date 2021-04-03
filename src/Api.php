<?php

namespace NsUtil;

class Api {

    private $body;
    private $headers;
    private $responseData = ['content' => [], 'error' => false];
    private $eficiencia;
    private $responseCode = 200;

    public function __construct() {
        // Obtenção dos headers
        $this->headers = $this->getAllHeaders();

        // Obtenção do body
        $this->body = $_POST;
        $dd = json_decode(file_get_contents('php://input'), true);
        if (is_array($dd)) {
            $this->body = array_merge($_POST, $dd);
        }

        Helper::recebeDadosFromView($this->body);

        $this->eficiencia = new \NsUtil\Eficiencia();
    }

    private function getAllHeaders() {
        if (!function_exists('getallheaders')) {
            $headers = [];
            foreach ($_SERVER as $name => $value) {
                if (substr($name, 0, 5) == 'HTTP_') {
                    $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
                }
            }
            return Helper::filterSanitize($headers);
        } else {
            return getallheaders();
        }
    }

    public function setError($mensagem, $code = 200) {
        $this->responseData['error'] = $mensagem;
        $this->responseCode = $code;
        return $this;
    }

    public function responseMerge(array $response) {
        $this->responseData = array_merge($this->responseData, $response);
        return $this;
    }

    public function addResponse($chave, $valor) {
        $this->responseMerge([$chave => $valor]);
        return $this;
    }

    public function response(array $response = [], int $responseCode = 0) {
        // Setar o codigo final de saida
        if ($responseCode > 0) {
            $this->responseCode = $responseCode;
        }


        // Adicionar parametros default
        $this->responseMerge($response);
        $this->responseMerge([
            'status' => $this->responseCode,
            'timeElapsed' => $this->eficiencia->end()->time,
        ]);

        // Sanitização
        $this->responseData['error'] = (($this->responseData['error']) ? $this->responseData['error'] : false);
        if ($this->responseData['error'] !== false || ($this->responseCode > 401 && stripos($this->responseData, 'SQLSTATE') === false)) {
            $this->responseData = ['error' => $this->responseData['error'], 'content' => []];
        }

        // Saida
        http_response_code($this->responseCode);
        echo json_encode($this->responseData);
        die();
    }

    function getBody() {
        return $this->body;
    }

    function getHeaders() {
        return $this->headers;
    }

    public static function result($code, $response) {
        $api = new Api();
        $api->response($response, $code);
    }

    public function setConfig(array $config = [], $page404 = '') {
        $router = new Router($page404);

        // Config para aplicação
        $data = array_merge([
            'headers' => $this->getHeaders(),
            'rota' => $router->getAllParam(1) . (($router->getAllParam(2)) ? '/' . $router->getAllParam(2) : ''),
            'acao' => 'ws_' . $router->getAllParam(2),
            'controller' => ucwords($router->getAllParam(1)),
            'includeFile' => $router->getIncludeFile(),
            'ParamsRouter' => $router->getAllParam(),
            'dados' => array_merge($this->getBody(), [
                'idFromRoute' => (int) $router->getAllParam(3),
            ]),
                ], $config);
        Config::init($data);
    }

    public function getUsernameAndPasswordFromBasicAuthorizationHeader() {
        $dt = explode(':', base64_decode(substr($this->getHeaders()['Authorization'], 6)));
        return [
            'username' => $dt[0],
            'password' => $dt[1]
        ];
    }

    public function getAuthorizationCodeFromHeader() {
        return substr($this->getHeaders()['Authorization'], 6);
    }

}
