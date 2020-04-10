<?php

namespace NsUtil;

class Helper {

    /**
     * 
     * @param type $configName
     * @param type $dirArquivoSample
     */
    public static function nsIncludeConfigFile($dirArquivoSample) {
        $dirArquivoSample = realpath($dirArquivoSample);
        $temp = explode(DIRECTORY_SEPARATOR, $dirArquivoSample);
        $configName = array_pop($temp);
        // importar arquivo de configuração desta aplicação.
        $t = explode(DIRECTORY_SEPARATOR, __DIR__);
        $file = 'nao-deve-achar.json';
        array_pop($t);
        array_pop($t);
        while (!file_exists($file)) {
            array_pop($t);
            $dir = implode(DIRECTORY_SEPARATOR, $t) . DIRECTORY_SEPARATOR;
            $file = $dir . 'composer.json';
        }
        self::mkdir($dir . DIRECTORY_SEPARATOR . 'nsConfig', 0600);
        $config = $dir . DIRECTORY_SEPARATOR . 'nsConfig' . DIRECTORY_SEPARATOR . $configName;
        if (!file_exists($config)) {
            copy($dirArquivoSample, $config);
            echo "<h1>nsConfig: É necessário criar o arquivo de configuração '[DIR_COMPOSER]/nsConfig/$configName'. <br/>Tentei gravar um modelo. Caso não esteja, existe um padrão na raiz da aplicação.</h1>";
            die();
        }
        include_once $config;
        $var = str_replace('.php', '', $configName);
        return $$var;
    }

    public static function sanitize($str) {
        return str_replace(" ", "_", preg_replace("/&([a-z])[a-z]+;/i", "$1", htmlentities(trim($str))));
    }

    public static function mkdir($path, $perm = 0777) {
        if (!is_dir(!$path)) {
            @mkdir($path, $perm, true);
        }
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

    public static function formatTextAllLowerFirstUpper($str) {
        $sempreMinusculas = ['a', 'ao', 'da', 'de', 'do', 'dos', 'das', 'ante', 'até', 'após', 'desde', 'em', 'entre', 'com', 'contra', 'para', 'por', 'perante', 'sem', 'sobe', 'sob'];
        $string = ucwords(mb_strtolower($str));
        foreach ($sempreMinusculas as $value) {
            $string = str_ireplace(" $value ", " " . mb_strtolower($value) . " ", $string);
        }
        return $string;
    }

    public static function arrayOrderBy(&$array, $element, $sort = 'ASC') {
        usort($array, function($a, $b) {
            global $element, $sort;
            if ($sort === 'ASC') {
                return $a[$element] < $b[$element];
            } else {
                return $a[$element] > $b[$element];
            }
        });
    }

    /**
     * Retorna a string no formato camelCase
     * @param type $string
     * @param array $prefixo
     * @return type string
     */
    public static function name2CamelCase($string, $prefixo = false) {
        $prefixo = array('mem_', 'sis_', 'anz_', 'aux_', 'app_');
        if (is_array($string)) {
            foreach ($string as $key => $value) {
                $out[self::name2CamelCase($key)] = $value;
            }
            return $out;
        }
        if (is_array($prefixo)) {
            foreach ($prefixo as $val) {
                $string = str_replace($val, "", $string);
            }
        }

        $string = str_replace('_', ' ', $string);
        $out = str_replace(' ', '', ucwords($string));
        $out{0} = mb_strtolower($out{0});
        return $out;
    }

    /**
     * Revert a string camelCase para camel_case
     * @param type $string
     * @return type string
     */
    public static function reverteName2CamelCase($string): string {
        $out = '';
        for ($i = 0; $i < strlen($string); $i++) {
            if ($string[$i] === mb_strtoupper($string[$i]) && $string[$i] !== '.') {
                $out .= (($i > 0) ? '_' : '');
                $string[$i] = mb_strtolower($string[$i]);
            }
            $out .= $string[$i];
        }
        return (string) $out;
    }

    /**
     * Cria a arvore de diretorios
     * @param type $filename
     * @return type
     */
    private static function createTreeDir($filename) {
        $path = str_replace('/', DIRECTORY_SEPARATOR, $filename);
        $parts = explode(DIRECTORY_SEPARATOR, $path);
        $file = array_pop($parts);
        $dir = implode(DIRECTORY_SEPARATOR, $parts);
        @mkdir($dir, 0777, true);
        return (object) ['path' => $dir, 'name' => $file];
    }

    public static function saveFile($filename, $name = false, $template = '<?=php Header("Location:/")', $mode = "w+") {
        $filename = $filename . (($name) ? '/' . $name : '');
        $file = self::createTreeDir($filename);
        if (file_exists($filename) && $mode !== 'SOBREPOR') {
            $file->name = '__NEW__' . $file->name;
        }
        $save = str_replace('/', DIRECTORY_SEPARATOR, $file->path . DIRECTORY_SEPARATOR . $file->name);
        unset($filename);
        file_put_contents($save, $template);
        return file_exists($save);
    }

    public static function eficiencia_init() {
        list($usec, $sec) = explode(' ', microtime());
        $ef = new Helper();
        $ef->start = (float) $sec + (float) $usec;
        return $ef;
    }

    public function endEficiencia() {
        // Terminamos o "contador" e exibimos
        list($usec, $sec) = explode(' ', microtime());
        $script_end = (float) $sec + (float) $usec;
        $elapsed_time = round($script_end - $this->start, 5);
        $minutos = (int) number_format((double) $elapsed_time / 60, 0);

        return 'Tempo de execução: ' . (($minutos > 0) ? $minutos . 'min' : '') . ceil(($elapsed_time - ($minutos * 60))) . 'segs. Memória utilizada: ' . round(((memory_get_peak_usage(true) / 1024) / 1024), 2) . 'Mb';
    }

    public static function deleteFile($filepath, $apagarDiretorio = false, $trash = false) {
        ///echo $filename;
        $filename = realpath($filepath);
        $t = explode(DIRECTORY_SEPARATOR, $filename);

        if (is_dir($filename)) {
            $dir = dir($filename);
            while ($arquivo = $dir->read()) {
                if ($arquivo != '.' && $arquivo != '..') {
                    self::deleteFile($filename . $arquivo, false, $trash);
                }
            }
            $dir->close();
            if ($apagarDiretorio) {
                unlink($filename);
            }
        } else {
            if (file_exists($filename)) {
                unlink($filename);
            }
        }
        if (!file_exists($filename)) {
            return false;
        }
    }

}
