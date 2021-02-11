<?php

namespace NsUtil;

// Helper funcionrs
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
     * Ira procurar num array multidimensional a chave e retornara um array correspondente aquela chave
     * @param type $array
     * @param type $chave
     */
    public static function arraySearchByKey(&$array, $chave, $valor): array {
        if (!is_array($array)) {
            throw new Exception('NSUtil (NSH120): Variavel não é um array');
        }
        $key = array_search($valor, array_column($array, $chave));
        return $array[$key];
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
    public static function createTreeDir($filename) {
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

    public static function directorySeparator(&$var) {
        $var = str_replace('/', DIRECTORY_SEPARATOR, $var);
    }

    public static function deleteDir($pasta) {
        self::directorySeparator($pasta);
        if (!is_dir($pasta)) {
            return true;
        }

        $iterator = new \RecursiveDirectoryIterator($pasta, \FilesystemIterator::SKIP_DOTS);
        $rec_iterator = new \RecursiveIteratorIterator($iterator, \RecursiveIteratorIterator::CHILD_FIRST);

        foreach ($rec_iterator as $file) {
            $file->isFile() ? unlink($file->getPathname()) : rmdir($file->getPathname());
        }

        /*
          $files = array_diff(scandir($dir), array('.', '..'));
          foreach ($files as $file) {
          $todel = $dir . DIRECTORY_SEPARATOR . $file;
          (is_dir($todel)) ? self::deleteDir($todel) : unlink($todel);
          }
          sleep(0.5);
         * 
         */
        rmdir($pasta);
        return is_dir($pasta);
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
        $filename = str_replace('/', DIRECTORY_SEPARATOR, $filepath);

        if (is_dir($filename)) {
            $dir = dir($filename);
            while ($arquivo = $dir->read()) {
                if ($arquivo != '.' && $arquivo != '..') {
                    self::deleteFile($filename . DIRECTORY_SEPARATOR . $arquivo, false, $trash);
                }
            }
            $dir->close();
            if ($apagarDiretorio) {
                rmdir($filename);
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
    public static function curlCall($url, $params = [], $method = 'GET', $header = ['Content-Type:application/json'], $ssl = true) {
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
            CURLOPT_SSL_VERIFYPEER => $ssl,
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
                    unset($dados[$key]);
                    continue;
                } else {
                    $dados[$key] = filter_var($value, FILTER_SANITIZE_STRING);
                    $dados[$key] = str_replace(['NS21', '&#34;'], ['&', '"'], $dados[$key]);
                }
                if (substr($key, 0, 2) === 'id') {
                    $dados[$key] = (int) filter_var($value, FILTER_VALIDATE_INT);
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

    public static function fileGetEncoding($filename) {
        $so = php_uname();
        if (stripos($so, 'linux') > -1) {
            $cmd = 'file -bi ' . $filename . ' | sed -e "s/.*[ ]charset=//"';
            $cod = shell_exec($cmd);
        } else {
            throw new \Exception('getFileEncoding somente funciona em sistemas Linux. O seu é ' . $so);
        }
        return trim($cod);
    }

    public static function fileConvertToUtf8($filepath, $output = false) {
        if (file_exists($filepath)) {
            $enc = self::fileGetEncoding($filepath);
            if ($enc !== 'utf-8') {
                $output = $output ? $output : $filepath;
                $cmd = "iconv -f $enc -t utf-8 -o $output $filepath ";
                //echo $cmd;
                $ret = shell_exec($cmd);
                if (strlen($ret) > 0) {
                    throw new \Exception("Erro ao converter arquivo $filepath pata UTF-8: " . $ret);
                }
            }
        } else {
            return 'File not exists';
        }
    }

    public static function fileSearchRecursive($file_name, $dir_init, $deep = 10) {
        $dirarray = explode(DIRECTORY_SEPARATOR, $dir_init);
        $filename = implode(DIRECTORY_SEPARATOR, $dirarray) . DIRECTORY_SEPARATOR . $file_name;
        $count = 0;
        while (!file_exists($filename) && $count < $deep) { // paths acima
            array_pop($dirarray);
            $filename = implode(DIRECTORY_SEPARATOR, $dirarray) . DIRECTORY_SEPARATOR . $file_name;
            $count++;
        }
        $filename = realpath($filename);
        if (!file_exists($filename)) {
            return false;
        } else {
            return $filename;
        }
    }

    public static function packerAndPrintJS($js) {
        $packer = new Packer($js, 'Normal', true, false, true);
        $packed_js = $packer->pack();
        echo "<script>$packed_js</script>";
    }

    /**
     * Verifica se o dados existe, se o conteudo é diferente de '' ou null ou false
     * @param type $value
     */
    public static function hasContent($value, $type = 'string') {
        if (is_array($value)) {
            foreach ($value as $item) {
                if (!self::hasContent($item, $type)) {
                    return false;
                }
            }
            return true;
        }
        $out = false;
        switch ($type) {
            case 'int':
                $t = (int) $value;
                $out = $t > 0;
                break;
            default:
                $out = strlen((string) $value) > 0;
                break;
        }
        return $out;
    }

    /**
     * Retorna um array com errors
     * @param array $dadosObrigatorios Array contendo array: ['value' => $dados['idCurso'], 'msg' => 'Informe a data inicial', 'type' => 'int'],
     */
    public static function validarCamposObrigatorios($dadosObrigatorios) {
        $error = [];
        foreach ($dadosObrigatorios as $item) {
            $has = self::hasContent($item['value'], (($item['type']) ? $item['type'] : 'string'));
            if ($has === false) {
                $error[] = $item['msg'];
            }
        }
        return $error;
    }

    public static function base64_to_jpeg($base64_string, $output_file) {
        // open the output file for writing
        $ifp = fopen($output_file, 'wb');

        // split the string on commas
        // $data[ 0 ] == "data:image/png;base64"
        // $data[ 1 ] == <actual base64 string>
        $data = explode(',', $base64_string);

        // we could add validation here with ensuring count( $data ) > 1
        fwrite($ifp, base64_decode($data[1]));

        // clean up the file resource
        fclose($ifp);

        return $output_file;
    }

    public static function gzReader($filename) {
        $out = '';
        // Raising this value may increase performance
        $buffer_size = 4096; // read 4kb at a time
        // Open our files (in binary mode)
        $file = gzopen($filename, 'rb');

        // Keep repeating until the end of the input file
        while (!gzeof($file)) {
            // Read buffer-size bytes
            // Both fwrite and gzread and binary-safe
            $out .= gzread($file, $buffer_size);
        }

        return $out;
    }

    public static function extractDataAtributesFromHtml($html) {
        $list = explode("data-", $html);
        unset($list[0]);
        $out = [];
        foreach ($list as $item) {
            $atribute = explode('"', $item);
            $out[str_replace('=', '', $atribute[0])] = $atribute[1];
        }
        return $out;
    }

    /**
     * Detecta se a chamada foi feita de um dispositivo mobile
     * @return boolean
     */
    public static function isMobile() {
        return preg_match("/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i", $_SERVER["HTTP_USER_AGENT"]);
        /*
          $useragent = $_SERVER['HTTP_USER_AGENT'];
          if (preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i', $useragent) || preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i', substr($useragent, 0, 4))) {
          return true;
          } else {
          return false;
          }
         */
    }

    public static function getIP() {
        $var = (($_SERVER['HTTP_X_FORWARDED_FOR']) ? 'HTTP_X_FORWARDED_FOR' : 'REMOTE_ADDR');
        return filter_input(INPUT_SERVER, $var, FILTER_SANITIZE_STRING);
    }

    /**
     * Ira filtrar um array de entrada de dados conforme os tipos identificados
     * @param type $var
     * @return type
     */
    public static function filterSanitize($var) {
        if (is_array($var)) {
            foreach ($var as $key => $value) {
                if (is_array($value)) {
                    $var[$key] = self::filterSanitize($value);
                } else {
                    if (substr($key, 0, 2) === 'id') {
                        $var[$key] = filter_var($value, FILTER_VALIDATE_INT);
                    }
                    if (stripos($key, 'email') > -1) {
                        $var[$key] = filter_var($value, FILTER_VALIDATE_EMAIL);
                    } else {
                        $var[$key] = filter_var($value, FILTER_SANITIZE_STRING);
                    }
                }
            }
            return $var;
        } else {
            return filter_var($var, FILTER_SANITIZE_STRING);
        }
    }

    /**
     * 
     * @param type $array 
     * @param type $filepath if false, retorna em text
     * @param type $withBom
     * @return type
     */
    public static function array2csv($array, $filepath = false, $withBom = true) {
        if ($filepath) {
            $fp = fopen($filepath, 'w');
            if ($withBom) {
                fputs($fp, $bom = ( chr(0xEF) . chr(0xBB) . chr(0xBF) ));
            }
            fputcsv($fp, array_keys($array[0])); // gravar o cabecalho
            foreach ($array as $linha) {
                fputcsv($fp, $linha);
            }
            fclose($fp);
            return file_exists($filepath);
        } else {
            $handle = fopen('php://temp', 'r+');
            foreach ($array as $line) {
                fputcsv($handle, $line, ';', '"');
            }
            rewind($handle);
            while (!feof($handle)) {
                $contents .= fread($handle, 8192);
            }
            fclose($handle);
            return $contents;
        }
    }

    public static function getSO() {
        return mb_strtolower(explode(' ', php_uname())[0]);
    }

    public static function parseInt($var) {
        return preg_replace("/[^0-9]/", "", $var);
    }

}
