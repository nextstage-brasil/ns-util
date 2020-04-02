<?php

use NsUtil\Util;

namespace NsUtil;

class Config {

    private static $nsUtilConfig = false;

    private function __construct() {
        return $this->init();
    }

    public static function init() {
        if (self::$nsUtilConfig) {
            return self::$nsUtilConfig;
        }
        $nsUtilConfig = [];
        // importar arquivo de configuração desta aplicação.
        $t = explode(DIRECTORY_SEPARATOR, __DIR__);
        for ($i = 0; $i < 4; $i++) {
            array_pop($t);
        }
        $path = implode(DIRECTORY_SEPARATOR, $t);
        $dir = $path . DIRECTORY_SEPARATOR . 'nsUtilConfig.php';
        if (!file_exists($dir)) {
            file_put_contents($dir, file_get_contents(__DIR__ . '/nsUtilConfig.php'));
            die('NsUtil: Necessário criar/configurar o arquivo "nsUtilConfig.php". Deve estar na mesma pasta onde se encontra o composer.json');
        }

        include $dir;

        ////Não é necessário alterações daqui em diante
        $nsUtilConfig['path_tmp'] = $nsUtilConfig['path'] . DIRECTORY_SEPARATOR . $nsUtilConfig['dir_to_write']; // diretorio absoluto com permissao de escrita
        $nsUtilConfig['path_uploadfile'] = $nsUtilConfig['path_tmp'] . DIRECTORY_SEPARATOR . 'files';
        $nsUtilConfig['path_downloads'] = $nsUtilConfig['path_tmp'] . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'd'; // path a partir da raiz para visualização de arquivos
        $nsUtilConfig['url_downloads'] = $nsUtilConfig['url'] . '/' . $nsUtilConfig['dir_to_write'] . '/files/d';


        // Criação de diretório obrigatórios
        Util::mkdir($nsUtilConfig['path_downloads']);
        Util::mkdir($nsUtilConfig['path_tmp']);
        Util::mkdir($nsUtilConfig['Local']['diretorio_local']);


        return $nsUtilConfig;
    }

}
