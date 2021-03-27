<?php

namespace NsUtil;

class Router {

    private $valorTotalURl;
    private $serverUri;
    private $pathView;
    private $param;
    private $route;
    private $allParam;
    private $entidade;
    private $arrayValues;
    private $includeFile;
    private $page404;

    /**
     * Inicia os dados do construtor
     * @return $this->pathView retorna o caminho = view/
     * @return $this->serverUri retorna o valor da REQUEST_URI ( LINK )
     * @return $this->valorTotalURl retorna o valoar da ( path quebrada PHP_URL_PATH ) para validação
     */
    public function __construct(string $page404Path, array $arrayValues = []) {
        $this->pathView = '/';
        $this->arrayValues = $arrayValues;

        //retorna o valor da REQUEST_URI ( LINK )
        $this->serverUri = $_SERVER['REQUEST_URI'];

        //retorna o valoar da ( path quebrada PHP_URL_PATH ) para validação
        $this->valorTotalURl = parse_url($this->serverUri, PHP_URL_PATH);

        // pagina 404
        Helper::directorySeparator($page404Path);
        if (file_exists($page404Path)) {
            $this->page404 = $page404Path;
        } else {
            $this->page404 = '404.html';
            //die('NsUtil::Router Error: Page404 não existe: ' . $page404Path);
        }
        $this->routes();
    }

    public function getIncludeFile() {
        return $this->includeFile;
    }

    public function getRoute() {
        return $this->route;
    }

    /**
     * 
     * @todo Gera a url para route
     * @return $diretorioAtual = Mostra o diretorio atual com getcwd + basename
     * @return $url  = explode a url
     * @return $search_array = procura na array o valor da base name
     * @return $url -> last = pega o ultimo nome da array
     */
    public function genereteUrl() {
        //Explode url vindo do $this->serverUri
        $url = explode('/', $this->serverUri);
        //Pega o nome do diretorio atual a base name do 
        $diretorioAtual = basename(getcwd());
        //Procura o valor do diretorio atual se tem na array $url
        $search_array = array_search($diretorioAtual, $url);

        //Contador de Valores para achar a dir na array
        $countValuesDir = 0;

        foreach ($url as $key) {
            if ($key == $diretorioAtual) {
                break;
            }
            $countValuesDir = $countValuesDir + 1;
        }
        $this->allParam = [];
        $this->param = [];

        //Se array search for falso 
        if (!$search_array) {
            //Retorna o caminho da url toda
            $url = $this->valorTotalURl;
            //retorna $url para rota
            /* return $url; */
        } else {

            //Começa a contar os campos apartir da nimeração do $countValuesDir, a partir dai puxa a rota!
            $url = preg_replace("/(.+)$url[$countValuesDir]/", '', $this->valorTotalURl);
        }
        //$url = $this->routePrefix . $url;
        //Retorna a Rota
        $this->allParam = Helper::filterSanitize(explode('/', $url));

        // IDENTIFICAR PARAMETROS
        $temp = explode('/', $url);
        $this->param[] = (int) $temp[2]; // obrigatoriamente um ID deve ser um inteiro
        unset($temp[0]); // zero, vazio
        unset($temp[1]); // 1: rota
        unset($temp[2]); // 2: ID
        foreach ($temp as $value) {
            $this->param[] = $value;
        }
        Helper::recebeDadosFromView($this->param);

        return $url;
    }

    /**
     * @todo pageError404() Erro se não encontrar pagina 404
     * @return http_response_code -> Adiciona no header error 404
     * @return $path -> Valor da Pasta e caminho do erro 404
     */
    public function pageError404() {
        return $this->page404;
    }

    /**
     * @todo   routes() Faz o caminho de Rotas
     * @param  $valorArray = valor de uso da rota exemple:
     * @return http_response_code -> Adiciona no header error 404
     * @return $path -> Valor da Pasta e caminho do erro 404
     */
    public function routes() {
        // IDENTIFICAR PARAMETROS
        $temp = explode('/', $this->genereteUrl());
        $rota = '/' . $temp[1];

        // Default
        $this->route = '/not-found';
        $this->includeFile = $this->pageError404();

        //Foreach dos dados da array $valorArray 
        foreach ($this->arrayValues as $rotaConfigurada => $dirConfigurado) {
            if (is_array($dirConfigurado)) {
                $rotaConfigurada = $dirConfigurado['prefix'];
                $dirConfigurado = $dirConfigurado['archive'];
            }
            if (empty($rota)) {
                $rota = '/';
            }
            if (Helper::compareString($rota, $rotaConfigurada) || Helper::compareString($rota, $rotaConfigurada . '/')) {
                $this->entidade = explode('/', $dirConfigurado)[0];
                $this->route = $rotaConfigurada;
                $this->includeFile = $dirConfigurado;
            }
        }
        return $this;
    }

    public function validaOd1(array $rotasDev, array $rotasAdmin = []) {
        $temp = explode('/', $this->genereteUrl());
        $rota = '/' . $temp[1];

        if (Helper::compareString((string) $this->param[0], '-999')) {
            return true;
        }

        if ($rota === '/') {
            $rota = '/logout';
            return true;
        }
        //soente develoopes
        foreach ($this->rotasDev as $item) {
            $item = '/' . $item;
            if ((Helper::compareString($rota, $item) || Helper::compareString($rota, $item . '/')) && !$_SESSION['od1']) {
                $rota = '/onlyDev';
                return true;
            }
        }

        //somente adminsitradores
        foreach ($this->rotasAdmin as $item) {
            $item = '/' . $item;
            if ((Helper::compareString($rota, $item) || Helper::compareString($rota, $item . '/')) && !UsuarioController::isUserAdmin()) {
                $rota = '/onlyAdmin';
                Log::auditoria('ACESSO-INDEVIDO', $_SERVER['REQUEST_URI']);
                return true;
            }
        }
    }

    private function htaccessWriter() {
        $file = '';
    }

    function getValorTotalURl() {
        return $this->valorTotalURl;
    }

    function getServerUri() {
        return $this->serverUri;
    }

    function getPathView() {
        return $this->pathView;
    }

    function getParam($key = false) {
        if ($key !== false) {
            return $this->param[$key];
        } else {
            return $this->param;
        }
    }

    function getAllParam($key = false) {
        if ($key !== false) {
            return $this->allParam[$key];
        } else {
            return $this->allParam;
        }
    }

    function getEntidade() {
        return $this->entidade;
    }

}
