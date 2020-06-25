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
        $from = "áàãâéêíóôõúüçÁÀÃÂÉÊÍÓÔÕÚÜÇ";
        $to = "aaaaeeiooouucAAAAEEIOOOUUC";
        $keys = array();
        $values = array();
        preg_match_all('/./u', $from, $keys);
        preg_match_all('/./u', $to, $values);
        $mapping = array_combine($keys[0], $values[0]);
        $str = strtr($str, $mapping);
        $str = preg_replace("/[^A-Za-z0-9]/", "_", $str);
        return $str;
        //return str_replace(" ", "_", preg_replace("/&([a-z])[a-z]+;/i", "$1", htmlentities(trim($str))));
        //return str_replace(" ", "_", preg_replace("/&([a-z])[a-z]+;/i", "$1", htmlentities(trim($str))));
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
        $elapsed_time = round($script_end - $this->start, 2);
        $minutos = (int) number_format((double) $elapsed_time / 60, 0);

        return 'Elapsed '
                . gmdate("H:i:s", (int) $elapsed_time)
                . ' with ' . round(((memory_get_peak_usage(true) / 1024) / 1024), 2) . 'Mb';
    }

    /**
     * Remove um arquivo em disco
     * @param type $filepath
     * @param type $apagarDiretorio
     * @param type $trash
     * @return boolean
     */
    public static function deleteFile($filepath, $apagarDiretorio = false, $trash = false) {
        ///echo $filename;
        $filename = realpath($filepath);
        $t = explode(DIRECTORY_SEPARATOR, $filename);
        /*
          $file = array_pop($t);
          $root = implode(DIRECTORY_SEPARATOR, $t);
          $adapter = new \League\Flysystem\Adapter\Local($root);
          $fs = new \League\Flysystem\Filesystem($adapter);
         */
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

    /**
     * Permite o uso em ambientes com SSL
     * @param type $url
     * @return type
     */
    public static function myFileGetContents($url) {
        $config = array(
            "ssl" => array(
                "verify_peer" => false,
                "verify_peer_name" => false,
            ),
        );

        $context = stream_context_create($config);

        return file_get_contents($url, false, $context);
    }

    /**
     * Método que encapsula uma chamada GET a uma url
     * @param string $url
     * @param array $params
     * @param string $method
     * @return Array
     */
    public static function curlCall($url, $params = [], $method = 'GET', $header = ['Content-Type:application/json']) {
        $options = [
            CURLOPT_URL => trim($url),
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POST => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 6.1; rv:8.0) Gecko/20100101 Firefox/8.0', //set user agent
            CURLOPT_COOKIEFILE => "/tmp/nsutil-curl-cookie.txt",
            CURLOPT_COOKIEJAR => "/tmp/nsutil-curl-cookiejar.txt", //set cookie 
            CURLOPT_RETURNTRANSFER => true, // return web page
            CURLOPT_FOLLOWLOCATION => true, // follow redirects
            CURLOPT_ENCODING => "", // handle all encodings
            CURLOPT_AUTOREFERER => true, // set referer on redirect
            CURLOPT_CONNECTTIMEOUT => 30, // timeout on connect
            CURLOPT_TIMEOUT => 30, // timeout on response
            CURLOPT_MAXREDIRS => 10, // stop after 10 redirects
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HEADER => false,
            CURLOPT_VERBOSE => false,
        ];
        $options[CURLOPT_HTTPHEADER] = $header;
        if (count($params) > 0) {
            switch ($method) {
                case 'POST':
                    $options[CURLOPT_POST] = true;
                    $options[CURLOPT_POSTFIELDS] = $params;
                    break;
                default:
                    $options[CURLOPT_POSTFIELDS] = json_encode($params);
            }
            $options[CURLOPT_POSTFIELDS] = json_encode($params);
        }
        $ch = curl_init();
        curl_setopt_array($ch, $options);
        $content = curl_exec($ch);
        //echo curl_getinfo($ch, CURLINFO_EFFECTIVE_URL).'<br/>';
        //echo 'error: ' . curl_errno($ch);
        $ret = (object) [
                    'content' => $content,
                    'errorCode' => curl_errno($ch),
                    'error' => ((curl_error($ch)) ? curl_error($ch) : curl_errno($ch)),
                    'status' => curl_getinfo($ch)['http_code'],
                    'http_code' => curl_getinfo($ch)['http_code']
        ];
        //Log::error(json_encode($ret));
        //echo json_encode($content);
        curl_close($ch);
        return $ret;
    }

    /**
     * Retorna um array associativo de um handle aberto via fopen
     * @param type $handle
     * @param type $explode
     * @param type $blockSize
     * @param type $enclosure
     * @return boolean
     */
    public static function myFGetsCsv($handle, $explode = ';', $blockSize = 1000, $enclosure = '"') {
        $data = fgetcsv($handle, $blockSize, $explode, $enclosure);
        if ($data === false || $data === null) {
            return false;
        }

        $line = implode('|M|', $data);
        $line = str_replace(['|EN|', '\\', ';', '"', "'", "\n", "\t", "\r\n", "0x0d"], [$explode, '', ' ', '', '', '', ' ', ' ', ' '], trim($line));
        $line = mb_convert_encoding($line, "UTF-8");
        $data = explode('|M|', $line);
        return $data;
    }

    /**
     * Conta a quantidade linhas em um arquivo CSV ou TXT
     * @param type $file
     * @return int
     */
    public static function linhasEmArquivo($file) {
        $l = 0;
        if ($f = fopen($file, "r")) {
            while ($d = fgets($f, 1000)) {
                $l++;
            }
        }
        unset($d);
        fclose($f);
        return $l;
    }

    /**
     * Trata de dados de entrada via APIs, removendo caracteres não desejados e nulls
     * @param type $dados
     * @return boolean
     */
    public static function recebeDadosFromView(&$dados) {
        if (!is_array($dados)) {
            return false;
        }
        foreach ($dados as $key => $value) {
            if (is_array($value)) {
                self::recebeDadosFromView($dados[$key]);
            } else {
                // tirar "undefined" do javascript
                if ($value === 'undefined' || $value === 'null' || $value === null) {
                    continue;
                } else {
                    $dados[$key] = filter_var($value, FILTER_SANITIZE_STRING);
                    $dados[$key] = str_replace(['NS21', '&#34;'], ['&', '"'], $dados[$key]);
                }
            }
        }
    }

    public static function depara(array $depara, array $dados, $retornaSomenteDepara = true) {
        if ($retornaSomenteDepara) {
            $out = [];
        } else {
            $out = $dados;
        }
        foreach ($depara as $key => $val) {
            $out[$key] = $dados[$val];
        }
        return $out;
    }
    
 public static function compareString($str1, $str2, $case = false) {
        if (!$case) {
            return (mb_strtoupper($str1) === mb_strtoupper($str2));
        } else {
            return ($str1 === $str2);
        }
    }

}
