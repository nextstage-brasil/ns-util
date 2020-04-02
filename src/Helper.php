<?php

namespace NsUtil;

class Helper {

    /**
     * 
     * @param type $configName
     * @param type $dirArquivoSample
     */
    public static function nsIncludeConfigFile($dirArquivoSample) {
        $temp = explode(DIRECTORY_SEPARATOR, self::setDirSeparator($dirArquivoSample));
        $configName = array_pop($temp);
        // importar arquivo de configuração desta aplicação.
        $t = explode(DIRECTORY_SEPARATOR, __DIR__);
        $file = 'composer.json';
        while (!file_exists($file)) {
            array_pop($t);
            $dir = implode(DIRECTORY_SEPARATOR, $t) . DIRECTORY_SEPARATOR;
            $file = $dir . 'composer.json';
        }
        self::mkdir($dir . DIRECTORY_SEPARATOR . 'nsConfigs', 0777);
        $config = $dir . $configName;
        if (!file_exists($config)) {
            copy($dirArquivoSample, $config);
            echo "<h1>É necessário criar o arquivo de configuração '[DIR_COMPOSER]/nsConfig/$configName'. Tentei gravar um modelo. Caso não esteja, existe um padrão na raiz da aplicação.</h1>";
            die();
        }
        // incluir arquivo de configuracao
        include_once $config;
    }

    public static function sanitize($str) {
        return str_replace(" ", "_", preg_replace("/&([a-z])[a-z]+;/i", "$1", htmlentities(trim($str))));
    }

    public static function mkdir($path, $perm = 0777) {
        if (!is_dir(!$path)) {
            mkdir($path, $perm, true);
        }
    }

    public static function setDirSeparator($string) {
        return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $string);
    }

    public static function convertAscii($string) {
        // Replace Single Curly Quotes
        $search[] = chr(226) . chr(128) . chr(152);
        $replace[] = "'";
        $search[] = chr(226) . chr(128) . chr(153);
        $replace[] = "'";
        // Replace Smart Double Curly Quotes
        $search[] = chr(226) . chr(128) . chr(156);
        $replace[] = '"';
        $search[] = chr(226) . chr(128) . chr(157);
        $replace[] = '"';
        // Replace En Dash
        $search[] = chr(226) . chr(128) . chr(147);
        $replace[] = '--';
        // Replace Em Dash
        $search[] = chr(226) . chr(128) . chr(148);
        $replace[] = '---';
        // Replace Bullet
        $search[] = chr(226) . chr(128) . chr(162);
        $replace[] = '*';
        // Replace Middle Dot
        $search[] = chr(194) . chr(183);
        $replace[] = '*';
        // Replace Ellipsis with three consecutive dots
        $search[] = chr(226) . chr(128) . chr(166);
        $replace[] = '...';
        // Apply Replacements
        $string = str_replace($search, $replace, $string);
        // Remove any non-ASCII Characters
        $string = preg_replace("/[^\x01-\x7F]/", "", $string);
        return $string;
    }

}
