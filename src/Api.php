<?php

namespace NsUtil;

use Closure;

class Api
{

    private $body = [];
    private $headers;
    private $responseData = ['content' => [], 'error' => false];
    private $eficiencia;
    private $responseCode = 200;
    private $config = [];
    private $router;
    private $simpleReturn = false; // Utilizado para deinfir se aapenas retornar o conteudo ou encerrar a aplicação
    private $successCallback;
    private $errorCallback;
    private $onResponse;
    private $authRequired;
    private $validators = [];

    // [Informational 1xx]
    const HTTP_CONTINUE = 100;
    const HTTP_SWITCHING_PROTOCOLS = 101;
    // [Successful 2xx]
    const HTTP_OK = 200;
    const HTTP_CREATED = 201;
    const HTTP_ACCEPTED = 202;
    const HTTP_NONAUTHORITATIVE_INFORMATION = 203;
    const HTTP_NO_CONTENT = 204;
    const HTTP_RESET_CONTENT = 205;
    const HTTP_PARTIAL_CONTENT = 206;
    // [Redirection 3xx]
    const HTTP_MULTIPLE_CHOICES = 300;
    const HTTP_MOVED_PERMANENTLY = 301;
    const HTTP_FOUND = 302;
    const HTTP_SEE_OTHER = 303;
    const HTTP_NOT_MODIFIED = 304;
    const HTTP_USE_PROXY = 305;
    const HTTP_UNUSED = 306;
    const HTTP_TEMPORARY_REDIRECT = 307;
    // [Client Error 4xx]
    const errorCodesBeginAt = 400;
    const HTTP_BAD_REQUEST = 400;
    const HTTP_UNAUTHORIZED = 401;
    const HTTP_PAYMENT_REQUIRED = 402;
    const HTTP_FORBIDDEN = 403;
    const HTTP_NOT_FOUND = 404;
    const HTTP_METHOD_NOT_ALLOWED = 405;
    const HTTP_NOT_ACCEPTABLE = 406;
    const HTTP_PROXY_AUTHENTICATION_REQUIRED = 407;
    const HTTP_REQUEST_TIMEOUT = 408;
    const HTTP_CONFLICT = 409;
    const HTTP_GONE = 410;
    const HTTP_LENGTH_REQUIRED = 411;
    const HTTP_PRECONDITION_FAILED = 412;
    const HTTP_REQUEST_ENTITY_TOO_LARGE = 413;
    const HTTP_REQUEST_URI_TOO_LONG = 414;
    const HTTP_UNSUPPORTED_MEDIA_TYPE = 415;
    const HTTP_REQUESTED_RANGE_NOT_SATISFIABLE = 416;
    const HTTP_EXPECTATION_FAILED = 417;
    // [Server Error 5xx]
    const HTTP_INTERNAL_SERVER_ERROR = 500;
    const HTTP_NOT_IMPLEMENTED = 501;
    const HTTP_BAD_GATEWAY = 502;
    const HTTP_SERVICE_UNAVAILABLE = 503;
    const HTTP_GATEWAY_TIMEOUT = 504;
    const HTTP_VERSION_NOT_SUPPORTED = 505;

    public function __construct($typeOut = 'json')
    {
        if ($typeOut === 'json') {
            header('Content-Type:application/json');
        }

        // Obtenção dos headers. Chaves sempre minusculas
        $this->headers = $this->getAllHeaders();

        // Obtenção do verbo
        $metodo = ((isset($_SERVER['REQUEST_METHOD'])) ? $_SERVER['REQUEST_METHOD'] : 'NULL');
        //        $pathinfo = ((isset($_SERVER['PATH_INFO'])) ? $_SERVER['PATH_INFO'] : '-');
        //        $recurso = explode("/", mb_substr((string) $pathinfo, 1));
        $this->body = [];

        switch ($metodo) {
            case 'PUT':
            case 'POST':
                // Obtenção do body
                $this->body = $_POST;
                $dd = json_decode(file_get_contents('php://input'), true);
                $dd = is_array($dd) ? $dd : [];
                $this->body = array_merge($_POST, $_GET, $dd);
                break;
            case 'GET':
                $this->body = $_GET;
                break;
            default:
        }

        $bodyOrigin = $this->body;
        Helper::recebeDadosFromView($this->body);

        $this->eficiencia = new \NsUtil\Eficiencia();

        // Config para aplicação
        $router = new Router('');
        $this->router = $router;

        // Variaveis adicionadas
        $rest = [
            'method' => (string) $metodo,
            'id' => (int) $router->getAllParam(2),
            'resource' => (string) $router->getAllParam(1),
            'action' => (string) $router->getAllParam(3),
        ];
        $this->config = [
            'rest' => $rest,
            'bodyOrigin' => $bodyOrigin,
            'headers' => $this->getHeaders(),
            'rota' => $router->getAllParam(1) . (($router->getAllParam(2)) ? '/' . $router->getAllParam(2) : ''), // '/' . $router->getAllParam(2),
            'acao' => 'ws_' . $router->getAllParam(2),
            'controller' => ucwords((string) $router->getAllParam(1)),
            'includeFile' => $router->getIncludeFile(),
            'ParamsRouter' => $router->getAllParam(),
            'dados' => array_merge($this->getBody(), [
                'idFromRoute' => (int) $router->getAllParam(3),
                '_rest' => $rest
            ]),
        ];

        // Definições API Rest
        switch ($metodo) {
            case 'GET':
                if (!$this->config['rest']['action']) {
                    $this->config['rest']['action'] = (($this->config['rest']['id'] > 0) ? 'read' : 'list');
                }
                break;
            case 'DELETE':
                $this->config['rest']['action'] = 'delete';
                break;
            case 'PUT':
            case 'POST':
                if (!$this->config['rest']['action']) {
                    $this->config['rest']['action'] = (($this->config['rest']['id'] > 0) ? 'update' : 'save');
                }
                break;
            default:
                $this->config['rest']['action'] = 'METHOD_NOT_FOUND';
        }
    }

    private function getAllHeaders()
    {
        if (!function_exists('getallheaders')) {
            $headers = [];
            foreach ($_SERVER as $name => $value) {
                if (mb_substr((string) $name, 0, 5) == 'HTTP_') {
                    $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr((string) $name, 5)))))] = $value;
                }
            }
            return Helper::filterSanitize($headers);
        } else {
            return getallheaders();
        }
    }

    /**
     * Retorna os dados enviados pela api restfull
     *
     * @return object
     */
    public function getRest(): object
    {
        return (object) $this->config['rest'];
    }

    /**
     * ATENÇÃO: Retorna o dado conforme foi enviado, sem nenhum tratamento de segurança. Use com atenção.
     * @param type $key
     */
    public function getOriginalData($key)
    {
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
    public function setError($mensagem, $code = 200)
    {
        $this->responseData['error'] = $mensagem;
        $this->responseCode = $code;
        return $this;
    }

    /**
     * Dado um array, faz o merge com o response body atual
     * @param array $response
     * @return $this
     */
    public function responseMerge(array $response)
    {
        $this->responseData = array_merge($this->responseData, $response);
        return $this;
    }

    /**
     * Adiciona ao body de saída uma chave => valor
     * @param type $chave
     * @param type $valor
     * @return $this
     */
    public function addResponse($chave, $valor)
    {
        $this->responseMerge([$chave => $valor]);
        return $this;
    }

    /**
     * Irá retornar o body em array ao inves de imrprimr e encerrar
     * @return type
     */
    public function getResponse()
    {
        return $this->response([], 0, false, true);
    }

    /**
     * Responde a requisição, encerrando o script
     * @param array $response
     * @param int $responseCode
     */
    public function response(array $response = [], int $responseCode = 0)
    {
        // Setar o codigo final de saida
        if ($responseCode > 0) {
            $this->responseCode = $responseCode;
        }

        if (count($response) > 0) {
            // caso content não venha nada, vou  colocar por padrão
            if (!isset($response['content']) && !isset($this->responseData['content'])) {
                //$response['content'] = false;
            }

            // Adicionar parametros default
            $this->responseMerge($response);
        }

        // Prepara os dados de forma padrão a ser entregue
        $this->getResponseData();

        // Saida
        http_response_code($this->responseCode);

        // Executar função anonima caso exista
        if (is_callable($this->errorCallback) && $this->responseCode > 399) {
            $this->successCallback = null;
            call_user_func($this->errorCallback, $this->responseData, $this->responseCode);
        }

        // Caso seja um desses códigos, nem imprimir nada
        if (array_search($this->responseCode, [501]) === false) {

            // Executar função anonima caso exista
            if (is_callable($this->onResponse)) {
                call_user_func($this->onResponse, $this->responseData, $this->responseCode);
            }

            echo json_encode($this->responseData, JSON_UNESCAPED_SLASHES);

            // Executar função anonima caso exista
            if (is_callable($this->successCallback)) {
                call_user_func($this->successCallback, $this->responseData, $this->responseCode);
            }
        }

        die();
    }

    /**
     * responde a aplicação com um erro
     * @param type $mensagem
     * @param int $code
     */
    public function error($mensagem, int $code = 0)
    {
        $this->setError($mensagem, $code);
        $this->response();
    }

    /**
     * Retorna o body da requisição
     * @return array
     */
    function getBody(): array
    {
        return $this->body;
    }

    /**
     * Retorna o headers da requisição
     * @return array
     */
    function getHeaders($keysToLower = false): array
    {
        if ($keysToLower) {
            foreach ($this->headers as $key => $val) {
                unset($this->headers[$key]);
                $this->headers[mb_strtolower($key)] = $val;
            }
        }
        return $this->headers;
    }

    public function getConfigData()
    {
        return $this->config;
    }

    public function getRouter(): Router
    {
        return $this->router;
    }

    /**
     * Estaticamente, cria uma instancia da API e responde o body com o código citado
     * @param int $code
     * @param array $response
     */
    public static function result(int $code, array $response, $type = 'json')
    {
        $api = new Api();
        $api->response($response, $code, $type);
    }

    /**
     * Recebe um array com as configuração e seta a configuração estatica de Config 
     * @param array $config
     * @param string $page404
     */
    public function setConfig(array $config = [], $page404 = ''): Api
    {
        $router = new Router($page404);

        // Config para aplicação
        $this->config = array_merge($this->config, $config);
        Config::init($this->config);

        return $this;
    }

    /**
     * Retorna configurações da rota API
     * @return string
     */
    public function getRota()
    {
        return $this->config['rota'];
    }

    /**
     * Retorna um array contento username e password enviado. 
     * 
     * Espera uma string em base64_encode contendo {username}:{password} no headers
     * @return array
     */
    public function getUsernameAndPasswordFromAuthorizationHeaders(): array
    {
        $dt = explode(':', base64_decode(mb_substr((string) $this->getHeaders()['Authorization'], 6)));
        return [
            'username' => $dt[0],
            'password' => $dt[1]
        ];
    }

    /**
     * Retorna a string enviada como Token no cabeçalho Authorization
     * @return string
     */
    public function getTokenFromAuthorizationHeaders(): string
    {
        $headers = $this->getHeaders();
        $auth = ((isset($headers['Authorization'])) ? $headers['Authorization'] : '');
        return (string) trim(substr($auth, 6));
    }

    /**
     *
     * @return array
     */
    function getResponseData(): array
    {
        // Preparar saida padrão
        if (!isset($this->responseData['content'])) {
            $this->responseData['content'] = [];
        }
        $this->responseMerge([
            'status' => $this->responseCode,
            'elapsedTime' => $this->eficiencia->end()->time,
        ]);

        // Sanitização
        $this->responseData['error'] = (($this->responseData['error'] !== false) ? $this->responseData['error'] : false);
        // if ($this->responseData['error'] !== false || ($this->responseCode > 401 && stripos((string) $this->responseData, 'SQLSTATE') === false)) {
        if ($this->responseCode > 401 && stripos((string) $this->responseData, 'SQLSTATE') === false) {
            $this->responseData = ['error' => $this->responseData['error'], 'content' => []];
        }
        return $this->responseData;
    }

    /**
     *
     * @return integer
     */
    function getResponseCode(): int
    {
        return $this->responseCode;
    }

    /**
     * Undocumented function
     *
     * @param integer $responseCode
     * @return self
     */
    function setResponseCode(int $responseCode): self
    {
        $this->responseCode = (int) $responseCode;
        return $this;
    }

    /**
     * Observa se a requisição é do tipo options e encerra respondendo as options
     * @param string $allowOrigin
     * @param string $allowMethods
     * @param string $allowHeaders
     * @return void
     */
    public static function options(string $allowOrigin = '*', string $allowMethods = 'GET,PUT,POST,DELETE,OPTIONS', string $allowHeaders = 'Data,Cache-Control,Referer,User-Agent,Origin,Accept,X-Requested-With,Content-Type,Access-Control-Request-Method,Access-Control-Request-Headers,Token,Authorization'): void
    {
        ## CORS
        \header('Access-Control-Allow-Origin: *');
        \header("Access-Control-Allow-Methods: $allowMethods");
        \header("Access-Control-Allow-Headers: $allowHeaders");
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            exit(0);
        }
    }

    /**
     * Verifica se a classe existe no path indicado, cria o controller padrão e entrega conforme os verbos para execução
     * @param string $namespace
     * string $allowOrigin = '*', string $allowMethods = 'GET,PUT,POST,DELETE,OPTIONS', string $allowHeaders = 'Data,Cache-Control,Referer,User-Agent,Origin,Accept,X-Requested-With,Content-Type,Access-Control-Request-Method,Access-Control-Request-Headers,Token,Authorization'
     */
    public static function restFull(string $namespace, Api $api = null): void
    {
        self::options();
        if (null === $api) {
            $api = new Api();
        }
        $api->setConfig();

        // Executar função anonima caso exista. Só existe função em caso de erro. O teste da funcao enviada é feito na chamada do metodo validator()
        foreach ($api->validators as $validator) {
            if (is_callable($validator)) {
                $api->successCallback = \null;
                call_user_func($validator, $api);
                die();
            }
        }

        $rest = (object) $api->getConfigData()['rest'];
        $class_name = $namespace . '\\' . ucwords((string) Helper::name2CamelCase($rest->resource));
        $class_name_controller = $class_name . 'Controller';
        $oldController = ucwords($rest->resource) . 'Controller';
        switch (true) {
            case (class_exists($class_name)):
                (new $class_name($api))();
                break;
            case (class_exists($class_name_controller)):
                (new $class_name_controller($api))();
                break;

            case (class_exists($oldController)):
                $aliases = [
                    'read' => 'getById',
                    'list' => 'getAll',
                    'create' => 'save',
                    'delete' => 'remove',
                    'update' => 'save',
                    'search' => 'getAll',
                    'new' => 'getNew'
                ];
                switch (true) {
                    case method_exists($oldController, "ws_" . $rest->action):
                        $action = "ws_" . $rest->action;
                        break;
                    case method_exists($oldController, "ws_" . $aliases[$rest->action]):
                        $action = "ws_" . $aliases[$rest->action];
                        break;
                    default:
                        $api->error('', Api::HTTP_NOT_IMPLEMENTED);
                        break;
                }
                $response = [];

                $code = 200;
                $controller = new $oldController();
                $data = array_merge(['id' => $rest->id], $api->getBody());
                $response['content'] = $controller->$action($data);

                if (isset($response['content']['error'])) {
                    $response['error'] = $response['content']['error'];
                }

                // se for getNew, remover os erros               
                if (Helper::compareString('ws_read', $action) || Helper::compareString('ws_getById', $action)) {
                    $response['error'] = false;
                    $response['content']['error'] = false;
                }

                $api->response($response, $code);

                break;

            default:
                http_response_code(Api::HTTP_NOT_IMPLEMENTED);
                die();
        }
    }

    /**
     * Undocumented function
     *
     * @param \Closure $successCallback
     * @return self
     */
    public function setSuccessCallback(\Closure $successCallback): self
    {
        $this->successCallback = $successCallback;
        return $this;
    }

    /**
     * Undocumented function
     *
     * @param \Closure $successCallback
     * @return self
     */
    public function onSuccess(\Closure $successCallback): self
    {
        $this->successCallback = $successCallback;
        return $this;
    }

    /**
     * Undocumented function
     *
     * @param \Closure $successCallback
     * @return self
     */
    public function onError(\Closure $errorCallback): self
    {
        $this->errorCallback = $errorCallback;
        return $this;
    }

    /**
     * Undocumented function
     *
     * @param \Closure $successCallback
     * @return self
     */
    public function validator(string $message, int $code, \Closure $rule): self
    {
        $ret = call_user_func($rule);
        if ($ret !== true) {
            $this->validators[] = function ($api) use ($code, $message) {
                $api->error($message, $code);
            };
        }
        return $this;
    }

    /**
     * Just to compatibility
     *
     * @param string $message
     * @param integer $code
     * @param \Closure $rule
     * @return self
     */
    public function middleware(string $message, int $code, \Closure $rule): self
    {
        return self::validator($message, $code, $rule);
    }

    /**
     * Undocumented function
     *
     * @param string $namespace
     * @return void
     */
    public function rest(string $namespace): void
    {
        self::restFull($namespace, $this);
    }

    /**
     * Set the value of onResponse
     *
     * @return  self
     */
    public function onResponse(Closure $onResponse)
    {
        $this->onResponse = $onResponse;
        return $this;
    }
}
