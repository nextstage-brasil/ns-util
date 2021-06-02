<?php

namespace NsUtil;

class Api {

    private $body;
    private $headers;
    private $responseData = ['content' => [], 'error' => false];
    private $eficiencia;
    private $responseCode = 200;
    private $config = [];
    private $router;

    public function __construct() {
        // Obtenção dos headers. Chaves sempre minusculas
        $this->headers = $this->getAllHeaders();

        // Obtenção do body
        $this->body = $_POST;
        $dd = json_decode(file_get_contents('php://input'), true);
        if (is_array($dd)) {
            $this->body = array_merge($_POST, $dd);
        }

        Helper::recebeDadosFromView($this->body);

        $this->eficiencia = new \NsUtil\Eficiencia();

        // Config para aplicação
        $router = new Router('');
        $this->router = $router;
        $this->config = [
            'headers' => $this->getHeaders(),
            'rota' => $router->getAllParam(1) . (($router->getAllParam(2)) ? '/' . $router->getAllParam(2) : ''), // '/' . $router->getAllParam(2),
            'acao' => 'ws_' . $router->getAllParam(2),
            'controller' => ucwords($router->getAllParam(1)),
            'includeFile' => $router->getIncludeFile(),
            'ParamsRouter' => $router->getAllParam(),
            'dados' => array_merge($this->getBody(), [
                'idFromRoute' => (int) $router->getAllParam(3),
            ]),
        ];
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

    /**
     * ATENÇÃO: Retorna o dado conforme foi enviado, sem nenhum tratamento de segurança. Use com atenção.
     * @param type $key
     */
    public function getOriginalData($key) {
        // Obtenção do body
        $b = $_POST;
        $dd = json_decode(file_get_contents('php://input'), true);
        if (is_array($dd)) {
            $b = array_merge($_POST, $dd);
        }
        return $b[$key];
    }

    /**
     * Adiciona a chave de error para response da Api
     * @param type $mensagem
     * @param type $code
     * @return $this
     */
    public function setError($mensagem, $code = 200) {
        $this->responseData['error'] = $mensagem;
        $this->responseCode = $code;
        return $this;
    }

    /**
     * Dado um array, faz o merge com o response body atual
     * @param array $response
     * @return $this
     */
    public function responseMerge(array $response) {
        $this->responseData = array_merge($this->responseData, $response);
        return $this;
    }

    /**
     * Adiciona ao body de saída uma chave => valor
     * @param type $chave
     * @param type $valor
     * @return $this
     */
    public function addResponse($chave, $valor) {
        $this->responseMerge([$chave => $valor]);
        return $this;
    }

    /**
     * Responde a requisição, encerrando o script
     * @param array $response
     * @param int $responseCode
     */
    public function response(array $response = [], int $responseCode = 0, $type = 'json') {
        // Setar o codigo final de saida
        if ($responseCode > 0) {
            $this->responseCode = $responseCode;
        }

        // caso content não venha nada, vou  colocar por padrão
        if ($response['content'] === null) {
            $response['content'] = false;
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
        if ($type === 'json') {
            header('Content-Type:application/json');
        }
        echo json_encode($this->responseData);
        die();
    }

    /**
     * responde a aplicação com um erro
     * @param type $mensagem
     * @param int $code
     */
    public function error($mensagem, int $code = 0) {
        $this->setError($mensagem, $code);
        $this->response();
    }

    /**
     * Retorna o body da requisição
     * @return type
     */
    function getBody() {
        return $this->body;
    }

    /**
     * Retorna o headers da requisição
     * @return type
     */
    function getHeaders($keysToLower = false) {
        if ($keysToLower) {
            foreach ($this->headers as $key => $val) {
                unset($this->headers[$key]);
                $this->headers[mb_strtolower($key)] = $val;
            }
        }
        return $this->headers;
    }

    public function getConfigData() {
        return $this->config;
    }

    public function getRouter(): Router {
        return $this->router;
    }

    /**
     * Estaticamente, cria uma instancia da API e responde o body com o código citado
     * @param type $code
     * @param type $response
     */
    public static function result($code, $response, $type='json') {
        $api = new Api();
        $api->response($response, $code, $type);
    }

    /**
     * Recebe um array com as configuração e seta a configuração estatica de Config 
     * @param array $config
     * @param type $page404
     */
    public function setConfig(array $config = [], $page404 = '') {
        $router = new Router($page404);

        // Config para aplicação
        $this->config = array_merge($this->config, $config);
        Config::init($this->config);
    }

    /**
     * Retorna configurações da API
     * @param type $key
     * @return type
     */
    public function getRota() {
        return $this->config['rota'];
    }

    /**
     * Retorna um array contento username e password enviado. 
     * 
     * Espera uma string em base64_encode contendo {username}:{password} no headers
     * @return array
     */
    public function getUsernameAndPasswordFromAuthorizationHeaders(): array {
        $dt = explode(':', base64_decode(substr($this->getHeaders()['Authorization'], 6)));
        return [
            'username' => $dt[0],
            'password' => $dt[1]
        ];
    }

    /**
     * Retorna a string enviada como Token no cabeçalho Authorization
     * @return string
     */
    public function getTokenFromAuthorizationHeaders(): string {
        return (string) substr($this->getHeaders()['Authorization'], 6);
    }

}
