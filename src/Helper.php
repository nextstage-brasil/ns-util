<?php

namespace NsUtil;

use Exception;
use stdClass;

// Helper funcionrs
class Helper
{

    /**
     * Retorna um caminho absoluto de um arquivo
     *
     * @param string $dirArquivoSample
     * @return void
     */
    public static function nsIncludeConfigFile(string $dirArquivoSample)
    {
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

    /**
     * Sanitize function
     *
     * @param string $str
     * @return string
     */
    public static function sanitize(string $str): string
    {
        $from = "áàãâéêíóôõúüçÁÀÃÂÉÊÍÓÔÕÚÜÇ";
        $to = "aaaaeeiooouucAAAAEEIOOOUUC";
        $keys = array();
        $values = array();
        preg_match_all('/./u', $from, $keys);
        preg_match_all('/./u', $to, $values);
        $mapping = array_combine($keys[0], $values[0]);
        $str = strtr($str, $mapping);
        $str = preg_replace("/[^A-Za-z0-9]/", "_", (string) $str);
        return $str;
        //return str_replace(" ", "_", preg_replace("/&([a-z])[a-z]+;/i", "$1", htmlentities(trim((string)$str))));
        //return str_replace(" ", "_", preg_replace("/&([a-z])[a-z]+;/i", "$1", htmlentities(trim((string)$str))));
    }

    public static function mkdir($path, $perm = 0777): void
    {
        if (!is_dir($path) && !is_file($path)) {
            @mkdir($path, $perm, true);
        }
    }

    public static function convertAscii($string)
    {
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

    public static function formatTextAllLowerFirstUpper($str)
    {
        $sempreMinusculas = ['a', 'ao', 'da', 'de', 'do', 'dos', 'das', 'ante', 'até', 'após', 'desde', 'em', 'entre', 'com', 'contra', 'para', 'por', 'perante', 'sem', 'sobe', 'sob'];
        $string = ucwords(mb_strtolower($str));
        foreach ($sempreMinusculas as $value) {
            $string = str_ireplace(" $value ", " " . mb_strtolower($value) . " ", $string);
        }
        return $string;
    }

    public static function arrayOrderBy(&$array, $element, $sort = 'ASC')
    {
        usort($array, function ($a, $b) use ($element, $sort) {
            if ($sort === 'ASC') {
                return $a[$element] > $b[$element];
            } else {
                return $a[$element] < $b[$element];
            }
        });
    }

    /**
     * Ira procurar num array multidimensional a chave e retornara um array correspondente aquela chave
     *
     * @param array $array
     * @param string $chave
     * @param string $valor
     * @return array
     */
    public static function arraySearchByKey(array &$array, string $chave, string $valor): array
    {
        if (!is_array($array)) {
            throw new Exception('NSUtil (NSH120): Variavel não é um array');
        }
        $key = array_search($valor, array_column($array, $chave));
        if (false !== $key) {
            return $array[$key];
        } else {
            return [];
        }
    }

    /**
     * Retorna a string no formato camelCase
     * @param string|array $string
     * @param array $prefixo
     * @return string|array
     */
    public static function name2CamelCase($string, $prefixo = false)
    {
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
        $string = str_replace('-', ' ', $string);
        //        $out = str_replace(' ', '', ucwords($string));
        $out = lcfirst(str_replace(' ', '', ucwords($string)));
        return $out;
    }

    /**
     * Revert a string camelCase para camel_case
     * @param string $string
     * @return string
     */
    public static function reverteName2CamelCase($string): string
    {
        $out = '';
        for ($i = 0; $i < strlen((string) $string); $i++) {
            if ($string[$i] === mb_strtoupper((string)$string[$i]) && $string[$i] !== '.') {
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
     * @return object
     */
    public static function createTreeDir($filename)
    {
        $path = str_replace('/', DIRECTORY_SEPARATOR, (string) $filename);
        $parts = explode(DIRECTORY_SEPARATOR, $path);
        $file = array_pop($parts);
        $dir = implode(DIRECTORY_SEPARATOR, $parts);
        self::mkdir($dir, 0777);
        // @mkdir($dir, 0777, true);
        return (object) ['path' => $dir, 'name' => $file];
    }

    public static function saveFile($filename, $name = false, $template = '<?php Header("Location:/");', $mode = "w+")
    {
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

    public static function eficiencia_init()
    {
        return "Method is disabled. Use class Eficiencia()";
        // return new Eficiencia();
        // list($usec, $sec) = explode(' ', microtime());
        // $ef = new Helper();
        // $ef->start = (float) $sec + (float) $usec;
        // return $ef;
    }

    public function endEficiencia()
    {
        return "Method is disabled. Use class Eficiencia()";
        // // Terminamos o "contador" e exibimos
        // list($usec, $sec) = explode(' ', microtime());
        // $script_end = (float) $sec + (float) $usec;
        // $elapsed_time = round($script_end - $this->start, 2);
        // $minutos = (int) number_format((float) $elapsed_time / 60, 0);

        // return 'Elapsed '
        //     . gmdate("H:i:s", (int) $elapsed_time)
        //     . ' with ' . round(((memory_get_peak_usage(true) / 1024) / 1024), 2) . 'Mb';
    }

    public static function directorySeparator(&$var): void
    {
        $var = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $var);
    }

    public static function deleteDir($pasta)
    {
        // Apenas para organizacao e compatibilidade de versoes
        return DirectoryManipulation::deleteDirectory($pasta);
    }


    /**
     * Remvoe um arquivop
     *
     * @param string $filepath
     * @param boolean $apagarDiretorio
     * @param boolean $trash
     * @return void
     */
    public static function deleteFile(string $filepath, bool $apagarDiretorio = false, bool $trash = false)
    {
        ///echo $filename;
        $filename = (string) str_replace('/', DIRECTORY_SEPARATOR, $filepath);

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
     * 
     * @param string $url
     * @param bool $ssl Tru or false para validação de SSL. Default false
     * @param int $timeout Timeout da chamada. Default 30 segundos
     * @return string
     */
    public static function myFileGetContents(string $url, bool $ssl = false, int $timeout = 30): string
    {
        $config = [
            'http' => [
                'timeout' => $timeout
            ],
            'ssl' => [
                'verify_peer' => $ssl,
                'verify_peer_name' => $ssl
            ]
        ];
        $context = stream_context_create($config);
        return file_get_contents($url, false, $context);
    }

    /**
     * Método que encapsula uma chamada a uma URL
     *
     * @param string $url
     * @param array $params
     * @param string $method
     * @param array $header
     * @param boolean $ssl
     * @param integer $timeout
     * @return object
     */
    public static function curlCall(string $url, array $params = [], string $method = 'GET', array $header = ['Content-Type:application/json'], bool $ssl = true, int $timeout = 30): object
    {
        // Remover cookie em excesso
        $cookiefile = Helper::getTmpDir() . DIRECTORY_SEPARATOR . 'NsUtilCurlCookie_' . md5((string) date('Ymd')) . '.txt';
        $options = [
            CURLOPT_URL => trim((string) $url),
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POST => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 6.1; rv:8.0) Gecko/20100101 Firefox/8.0', //set user agent
            CURLOPT_COOKIEFILE => $cookiefile,
            CURLOPT_COOKIEJAR => $cookiefile,
            CURLOPT_RETURNTRANSFER => true, // return web page
            CURLOPT_FOLLOWLOCATION => true, // follow redirects
            CURLOPT_ENCODING => "", // handle all encodings
            CURLOPT_AUTOREFERER => true, // set referer on redirect
            CURLOPT_CONNECTTIMEOUT => 30, // timeout on connect
            CURLOPT_TIMEOUT => (int) $timeout, // timeout on response
            CURLOPT_MAXREDIRS => 10, // stop after 10 redirects
            CURLOPT_SSL_VERIFYPEER => $ssl,
            CURLOPT_SSL_VERIFYSTATUS => $ssl,
            CURLOPT_HEADER => true,
            CURLOPT_VERBOSE => false,
        ];
        $options[CURLOPT_HTTPHEADER] = $header;

        if (count($params) > 0) {
            $options[CURLOPT_POST] = $method === 'POST';
            $options[CURLOPT_POSTFIELDS] = array_search(
                'content-type:application/json',
                array_map('mb_strtolower', $header)
            ) !== false
                ? json_encode($params)
                : $params;

            if ($method === 'GET') {
                $options[CURLOPT_POSTFIELDS] = null;
                $options[CURLOPT_URL] .= '?' . http_build_query($params);
            }
        }
        $ch = curl_init();
        curl_setopt_array($ch, $options);
        $output = curl_exec($ch);
        $urlInfo = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

        // headers
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $body = substr((string) $output, $header_size);

        // echo $urlInfo . PHP_EOL;
        // echo 'error: ' . curl_errno($ch);
        // var_export($body);
        // echo is_string($options[CURLOPT_POSTFIELDS]) ? $options[CURLOPT_POSTFIELDS] : json_encode($options[CURLOPT_POSTFIELDS]);
        // echo PHP_EOL;


        $headers = [];
        $output = rtrim((string) $output);
        $data = explode("\n", $output);
        $headers['status'] = $data[0];
        array_shift($data);
        foreach ($data as $part) {
            //some headers will contain ":" character (Location for example), and the part after ":" will be lost, Thanks to @Emanuele
            $middle = explode(":", $part, 2);
            //Supress warning message if $middle[1] does not exist, Thanks to @crayons
            if (!isset($middle[1])) {
                $middle[1] = null;
            }
            $headers[trim((string) $middle[0])] = trim((string) $middle[1]);
        }

        $ret = (object) [
            'content' => $body,
            'errorCode' => curl_errno($ch),
            'error' => ((curl_error($ch)) ? curl_error($ch) : curl_errno($ch)),
            'status' => (int) curl_getinfo($ch)['http_code'],
            'http_code' => (int) curl_getinfo($ch)['http_code'],
            'headers' => $headers,
            'url' => $urlInfo
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
    public static function myFGetsCsv($handle, $explode = ';', $blockSize = 0, $enclosure = '"')
    {
        $data = fgetcsv($handle, $blockSize, $explode, $enclosure);
        if ($data === false || $data === null) {
            return false;
        }

        $from = ['|EN|', "\n", "\t", '\r\n', "0x0d", '\\', ';', "'"];
        $to = [$explode, '', ' ', ' ', ' ', '', ' ', ''];

        $line = implode('|M|', $data);
        //$line = str_replace(['|EN|', ';', "'", '\\n', "\t", "\r\n", "0x0d", '\\'], [$explode, ' ', '', chr(13), ' ', ' ', ' ', ''], trim((string)$line));
        //$line = str_replace(['|EN|', "\n", "\t", "\r\n", "0x0d", '\\', ';', "'"], [$explode, '', ' ', ' ', ' ', '', ' ', ''], trim((string)$line));
        $line = str_replace($from, $to, trim((string) $line));
        $line = mb_convert_encoding($line, "UTF-8");
        $data = explode('|M|', $line);

        return $data;
    }

    /**
     * Conta a quantidade linhas em um arquivo CSV ou TXT
     * @param string $file
     * @return int
     */
    public static function linhasEmArquivo(string $file)
    {
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
    public static function recebeDadosFromView(&$dados)
    {
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
                    $dados[$key] = Filter::string($value);
                    $dados[$key] = str_replace(['NS21', '&#34;'], ['&', '"'], $dados[$key]);
                }
                if (substr((string) $key, 0, 2) === 'id') {
                    $dados[$key] = (int) filter_var($value, FILTER_VALIDATE_INT);
                }
            }
        }
    }

    public static function depara(array $depara, array $dados, $retornaSomenteDepara = true)
    {
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

    public static function compareString($str1, $str2, $case = false)
    {
        if (!$case) {
            return (mb_strtoupper((string)$str1) === mb_strtoupper((string)$str2));
        } else {
            return ($str1 === $str2);
        }
    }

    public static function fileGetEncoding($filename)
    {
        $so = php_uname();
        $cod = '';
        if (stripos($so, 'linux') > -1) {
            $cmd = 'file -bi ' . $filename . ' | sed -e "s/.*[ ]charset=//"';
            $cod = shell_exec($cmd);
        } else {
            throw new \Exception('getFileEncoding somente funciona em sistemas Linux. O seu é ' . $so);
        }
        return trim((string) $cod);
    }

    public static function fileConvertToUtf8($filepath, $output = false)
    {
        if (file_exists($filepath)) {
            $enc = self::fileGetEncoding($filepath);
            if (strlen($enc) > 0 && $enc !== 'utf-8' && stripos($enc, 'ascii') === false) {
                $output = $output ? $output : $filepath;
                $cmd = "iconv -f $enc -t utf-8 -o $output $filepath ";
                $ret = shell_exec($cmd);
                if (strlen((string) $ret) > 0) {
                    throw new \Exception("Erro ao converter arquivo $filepath pata UTF-8: " . $ret);
                }
            }
        } else {
            return 'File not exists';
        }
    }

    public static function fileSearchRecursive($file_name, $dir_init, $deep = 10)
    {
        $dirarray = explode(DIRECTORY_SEPARATOR, $dir_init);
        $filename = implode(DIRECTORY_SEPARATOR, $dirarray) . DIRECTORY_SEPARATOR . $file_name;
        $count = 0;
        while (!@file_exists($filename) && $count < $deep) { // paths acima
            array_pop($dirarray);
            $filename = implode(DIRECTORY_SEPARATOR, $dirarray) . DIRECTORY_SEPARATOR . $file_name;
            $count++;
        }
        $filename = @realpath($filename);
        if (!@file_exists($filename)) {
            return false;
        } else {
            return $filename;
        }
    }

    public static function packerAndPrintJS($js)
    {
        $packer = new Packer($js, 'Normal', true, false, true);
        $packed_js = $packer->pack();
        echo "<script>$packed_js</script>";
    }

    /**
     * Verifica se o dados existe, se o conteudo é diferente de '' ou null ou false
     * @param type $value
     */
    public static function hasContent($value, $type = 'string')
    {
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
                $out = strlen((string) (string) $value) > 0;
                break;
        }
        return $out;
    }

    /**
     * Retorna um array com errors
     * @param array $dadosObrigatorios Array contendo array: ['value' => $dados['idCurso'], 'msg' => 'Informe a data inicial', 'type' => 'int'],
     */
    public static function validarCamposObrigatorios($dadosObrigatorios)
    {
        $error = [];
        foreach ($dadosObrigatorios as $item) {
            $has = self::hasContent($item['value'], (($item['type']) ? $item['type'] : 'string'));
            if ($has === false) {
                if ($item['key']) {
                    //$item['msg'] = '{' . $item['key'] . '}: ' . $item['msg'];
                    $error[$item['key']] = $item['msg'];
                } else {
                    $error[] = $item['msg'];
                }
            }
        }
        return $error;
    }

    public static function base64_to_jpeg($base64_string, $output_file)
    {
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

    public static function gzReader($filename)
    {
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

    public static function extractDataAtributesFromHtml($html)
    {
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
    public static function isMobile()
    {
        return preg_match("/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i", (string) $_SERVER["HTTP_USER_AGENT"]);
    }

    /**
     * Retorna o IP em uso pelo cliente
     *
     * @return string
     */
    public static function getIP(): string
    {
        $var = (($_SERVER['HTTP_X_FORWARDED_FOR']) ? 'HTTP_X_FORWARDED_FOR' : 'REMOTE_ADDR');
        $ip = filter_input(INPUT_SERVER, $var, FILTER_DEFAULT);
        return Filter::string($ip);
    }

    /**
     * Ira filtrar um array de entrada de dados conforme os tipos identificados
     * @param type $var
     * @return string
     */
    public static function filterSanitize($var)
    {
        if (is_array($var)) {
            foreach ($var as $key => $value) {
                if (is_array($value)) {
                    $var[$key] = self::filterSanitize($value);
                } else {
                    if (substr((string) $key, 0, 2) === 'id') {
                        $var[$key] = filter_var($value, FILTER_VALIDATE_INT);
                    }
                    if (stripos($key, 'email') > -1) {
                        $var[$key] = filter_var($value, FILTER_VALIDATE_EMAIL);
                    } else {
                        $var[$key] = Filter::string($value);
                    }
                }
            }
            return $var;
        } else {
            return Filter::string($var);
        }
    }

    /**
     * 
     * @param array $array 
     * @param string $filepath if false, retorna em text
     * @param bool $withBom
     * @return mixed
     */

    public static function array2csv(array $array, ?string $filepath = null, bool $withBom = true, string $delimiter = ',')
    {
        // Manter o padrão entre as chaves
        $trataed = [];
        $keys = array_keys($array[0]);
        foreach ($array as $val) {
            $ni = [];
            foreach ($keys as $k) {
                $ni[$k] = ((isset($val[$k])) ? $val[$k] : '');
            }
            $trataed[] = $ni;
        }
        $array = $trataed;

        // $delimiter = ';';
        if (null !== $filepath) {
            $fp = fopen($filepath, 'w');
            // BOM
            if ($withBom) {
                fputs($fp, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));
            }

            // Gravar o cabeçalho
            $keys = array_keys($array[0]);
            fputcsv($fp, $keys, $delimiter);

            // Gravar dados
            foreach ($array as $linha) {
                foreach ($linha as $key => $val) {
                    if (is_array($val) || is_object($val)) {
                        $linha[$key] = json_encode($val, JSON_HEX_QUOT | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE);
                    }
                }
                fputcsv($fp, $linha, $delimiter);
            }

            fclose($fp);
            return file_exists($filepath);
        } else {
            $handle = fopen('php://temp', 'r+');
            foreach ($array as $line) {
                fputcsv($handle, $line, $delimiter, '"');
            }
            rewind($handle);
            $contents = '';
            while (!feof($handle)) {
                $contents .= fread($handle, 8192);
            }
            fclose($handle);
            return $contents;
        }
    }

    public static function getSO()
    {
        return mb_strtolower(explode(' ', php_uname())[0]);
    }

    /**
     * Remove all characters is not a number
     *
     * @param mixed $var
     * @return int
     */
    public static function parseInt($var)
    {
        return preg_replace("/[^0-9]/", "", (string) $var);
    }

    /**
     * json_dceo com tratamento de alguns carateres que causam sujeira
     * @param string $json
     * @return array
     */
    public static function jsonToArrayFromView($json)
    {
        return json_decode(str_replace('&#34;', '"', (string) $json), true);
    }

    public static function objectPHP2Array(\stdClass $object)
    {
        return json_decode(json_encode($object), true);
    }

    /**
     * Com base na data informada, retorna um array verificando se é feriado, prox dia util....
     * 
     * @param type $date formato yyyy-mm-dd
     * @return array {"isDiaUtil":true,"proxDiaUtil":"2021-03-19","ultDiaUtil":"2021-03-17"}
     */
    public static function feriado($date)
    {
        $url = 'https://syncpay.usenextstep.com.br/api/util/feriado/' . self::parseInt($date);
        $ret = self::curlCall($url)->content;
        $ret1 = json_decode($ret, true);
        return $ret1['content'];
    }

    /**
     * Com base na data informaada, calcula a proxima data útil no calendario, com prazo N estabelecido
     * @param type $vencimento Data inicial, no formato yyyy-mm-dd
     * @param type $prazo Prazo em 
     * @return string String, no formato yyyy-mm-dd
     */
    public static function calculaVencimentoUtil($vencimento, $prazo = 0)
    {
        if ($prazo > 0) {
            // rotina para varrer somente dias úteis
            $count = 0;
            while ($count < $prazo) {
                $ret = self::feriado($vencimento);
                $vencimento = $ret['proxDiaUtil'];
                $count++;
            }
        }
        return $vencimento;
    }

    /**
     * Ira retornar um array contendo apenas os valores das chaves selecionadas
     * @param array $origem
     * @param array $chaves
     */
    public static function arrayReduceKeys(array $origem, array $chaves)
    {
        $out = [];
        foreach ($chaves as $val) {
            $out[$val] = $origem[$val];
        }
        return $out;
    }

    /**
     * Ira buscar o path da aplicação, antes da pasta /vendor
     */
    public static function getPathApp()
    {
        return str_replace(DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'nextstage-brasil' . DIRECTORY_SEPARATOR . 'ns-util' . DIRECTORY_SEPARATOR . 'src', '', __DIR__);
    }

    /**
     * Retorna um valor baseado em um tipo
     * @param mixed $string
     * @param string $type
     */
    public static function getValByType($string, string $type)
    {
        switch ($type) {
            case 'int':
            case 'serial':
            case 'serial4':
            case 'serial8':
                $out = filter_var($string, FILTER_VALIDATE_INT);
                if (!$out) {
                    $out = null;
                }
                break;
            case 'double':
                $out = filter_var($string, FILTER_VALIDATE_FLOAT);
                if (!$out) {
                    $out = null;
                }
                break;
            case 'html':
                $out = $string;
                break;
            default:
                $out = Filter::string($string);
                break;
        }
        return $out;
    }

    public static function formatDate($date, $escolha = 'arrumar', $datahora = false, $alterarTimeZone = false, $timezone = 'America/Sao_Paulo')
    {
        return (new Format($date, $timezone))->date($escolha, $datahora, $alterarTimeZone);
    }

    public static function formatCep($cep)
    {
        return (new Format($cep))->cep();
    }

    public static function formatCpfCnpj($var)
    {
        $var = self::parseInt($var);
        if (strlen((string) $var) === 11) { // cpf
            $out = substr((string) $var, 0, 3) . '.' . substr((string) $var, 3, 3) . '.' . substr((string) $var, 6, 3) . '-' . substr((string) $var, 9, 2);
        } else if (strlen((string) $var) === 14) { // cnpj
            $out = substr((string) $var, 0, 2) . '.' . substr((string) $var, 2, 3) . '.' . substr((string) $var, 5, 3) . '/' . substr((string) $var, 8, 4) . '-' . substr((string) $var, 12, 2);
        } else {
            $out = $var;
        }
        return $out;
    }

    /**
     * Destaca um texto em string
     *
     * @param string $texto
     * @param string $search
     * @return void
     */
    public static function highlightText(string $texto, string $search)
    {
        $searchsan = self::sanitize($search);
        $textosan = self::sanitize($texto);
        $inicio = stripos($textosan, $searchsan);
        if ($inicio >= 0) {
            $trecho = \mb_substr((string) $texto, $inicio, strlen((string) $search));
            $texto = \str_replace($trecho, '<span class="ns-highlight-text">' . $trecho . '</span>', $texto);
        }
    }

    public static function getPsr4Name($dir = '')
    {
        $dir = ((strlen((string) $dir)) ? $dir : Helper::getPathApp());
        $composer = file_get_contents(Helper::fileSearchRecursive('composer.json', $dir));
        $composer = json_decode($composer, true);
        return str_replace('\\', '', key($composer['autoload']['psr-4']));
    }

    public static function getPhpUser()
    {
        $uid = posix_getuid();
        $userinfo = posix_getpwuid($uid);
        return $userinfo;
    }

    // Define uma função que poderá ser usada para validar e-mails usando regexp
    public static function validaEmail($email)
    {
        //return
        $er = "/^(([0-9a-zA-Z]+[-._+&])*[0-9a-zA-Z]+@([-0-9a-zA-Z]+[.])+[a-zA-Z]{2,6}){0,1}$/";
        if (preg_match($er, (string) $email)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Verifica se a string é uma base64_encoded valida
     * @param type $data
     * @return boolean
     */
    public static function isBase64Encoded($string)
    {
        if (preg_match('%^[a-zA-Z0-9/+]*={0,2}$%', (string) $string)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Prepara um valor para o formato americano decimal
     *
     * @param mixed $var
     * @return float|mixed
     */
    public static function decimalFormat($var)
    {
        if (stripos($var, ',') > -1) { // se achar virgula, veio da view, com formato. da base, nao vem virgula
            $var = self::parseInt($var);
            $var = substr((string) $var, 0, strlen((string) $var) - 2) . "." . substr((string) $var, strlen((string) $var) - 2, 2);
        }
        return $var;
    }

    /**
     * Retorna um array com as variaveis para paginação de resultados
     * @param int $page Pagina atual
     * @param int $limitPerPage limite por página
     * @param int $totalRegs total de registros do request
     * @return array
     */
    public static function pagination(int $atualPage, int $limitPerPage, int $totalRegs): array
    {
        $ret = [];
        $ret['atualPage'] = $atualPage;
        $ret['nextPage'] = ((($limitPerPage * ($atualPage + 1)) < $totalRegs) ? ($atualPage + 1) : false);
        $ret['prevPage'] = (($atualPage > 0) ? $atualPage - 1 : false);
        $ret['totalPages'] = ceil(($totalRegs / $limitPerPage));
        return $ret;
    }

    /**
     * Método que converte um array php para o formato ".env" files
     *
     * @param array $config
     * @return array
     */
    public static function array2env(array $config)
    {
        $out = [];
        foreach ($config as $key => $value) {
            if (is_object($value)) {
                $value = \json_decode(json_encode($value), true);
            }
            if (\is_array($value)) {
                $out[] = "\n[$key]";
                $temp = self::array2env($value);
                $out[] = implode("\t\n", $temp);
                $out[] = "\n";
            }
            $out[] = "$key=\"$value\"";
        }
        return $out;
    }

    /**
     * Return de icon name
     *
     * @param string $filename
     * @return string
     */
    public static function getThumbsByFilename(string $filename): string
    {
        $t = explode('.', $filename);
        $extensao = array_pop($t);
        $out = '';
        switch (mb_strtoupper((string)$extensao)) {
            case 'XLSX':
            case 'XLS':
                $out = 'file-excel-o';
                break;
            case 'PDF':
                $out = 'file-pdf-o';
                break;
            case 'PNG':
            case 'JPG':
            case 'GIF':
            case 'JPEG':
                $out = 'file-image-o';
                break;
            case 'ZIP':
                $out = 'file-archive-o';
                break;
            case 'MP3':
            case 'AAC':
                $out = 'file-audio-o';
                break;
            case 'AVI':
            case 'MP4':
                $out = 'file-video-o';
                break;
            default:
                $out = 'file';
        }
        return $out;
    }

    /**
     * Executa uma busca no viacep e retorna
     * @param string $cep
     * @return \stdClass
     */
    public static function buscacep(string $cep): \stdClass
    {
        $cepSearch = self::parseInt($cep);
        if (strlen($cepSearch) < 8) {
            return (object) ['error' => "Quantidade de caracteres inválido: '$cep'"];
        }
        if ($cepSearch < 1) {
            return (object) ['error' => "CEP inválido para pesquisa: '$cep'"];
        }

        $url = "https://viacep.com.br/ws/$cepSearch/json/";
        $ret = file_get_contents($url);
        return (object) json_decode($ret);
    }

    public static function setPaginacao(int $registros, int $limit, array &$out, array &$dados): void
    {
        $paginas = (int) ($registros / $limit);
        $out['pagination'] = [];
        $out['pagination']['atualPage'] = (int) $dados['pagina'];
        $out['pagination']['totalPages'] = (($paginas * $limit < $registros) ? $paginas + 1 : $paginas);
        $out['pagination']['totalItens'] = $registros;
        $out['pagination']['nextPage'] = $out['pagination']['page'] + 1 < $out['pagination']['totalPages'] ? $out['pagination']['page'] + 1 : null;
        $out['pagination']['previusPage'] = $out['pagination']['page'] - 1 >= 0 ? $out['pagination']['page'] - 1 : null;
        $out['pagination']['initalPage'] = 0;
        $out['pagination']['lastPage'] = $out['pagination']['totalPages'];
    }

    /**
     * @return string
     */
    public static function getTmpDir(): string
    {
        // @codeCoverageIgnoreStart
        if (function_exists('sys_get_temp_dir')) {
            $tmp = sys_get_temp_dir();
        } elseif (!empty($_SERVER['TMP'])) {
            $tmp = $_SERVER['TMP'];
        } elseif (!empty($_SERVER['TEMP'])) {
            $tmp = $_SERVER['TEMP'];
        } elseif (!empty($_SERVER['TMPDIR'])) {
            $tmp = $_SERVER['TMPDIR'];
        } else {
            $tmp = getcwd();
        }

        return $tmp;
    }

    /**
     * @return string
     */
    public static function getHost(): string
    {
        return php_uname('n');
    }

    public static function addConditionFromAPI(array &$condition, array $dados): void
    {
        if (isset($dados['conditions']) && is_array($dados['conditions'])) {
            $newConditions = [];
            foreach ($dados['conditions'] as $key => $val) {
                $isJson = json_decode($val, true);
                if ($isJson) {
                    $val = $isJson;
                }
                $newConditions[$key . '_addedConditionFromAPI'] = $val;
            }
            $condition = array_merge($condition, $newConditions);
        }
    }


    /**
     * retorna o texto entre a tag selecionada
     *
     * @param string $msg
     * @param string $tag
     * @return array
     */
    public static function getTextByTag(string $msg, string $tag, string $tagOpen = '<', $tagClose = '>'): array
    {
        $out = [];
        $termOpen = $tagOpen . $tag . $tagClose;
        $termClose = $tagOpen . '\/' . $tag . $tagClose;

        if (stripos($msg, $termOpen) !== false) {
            // preg_match('/' . $termOpen . '(.*?)' . $termClose . '/s', $msg, $match);
            preg_match('/' . $termOpen . '(.*?)' . $termClose . '/s', $msg, $match);
            unset($match[0]);
            $out = array_map(function ($item) {
                return trim(str_replace('\n', '<br/>', strip_tags((string)$item)));
            }, $match);
        }
        return is_array($out) ? $out : [];
    }

    /**
     * Método que retorna um array com as diferenças entre dois arrays
     *
     * @param array $arrayNew
     * @param array $arrayOld
     * @param array $keysToIgnore
     * @return array
     */
    public static function arrayDiff(array $arrayNew, array $arrayOld, array $keysToIgnore = [])
    {
        $out = [];

        $alteradosNovo = array_diff_assoc($arrayNew, $arrayOld);
        $alteradosAntigo = array_diff_assoc($arrayOld, $arrayNew);
        unset($alteradosNovo['error']);

        if (count($alteradosNovo) > 0) {
            foreach ($alteradosNovo as $key => $value) {

                if (array_search($key, $keysToIgnore) !== false) {
                    continue;
                }

                $json = is_string($value) ? json_decode((string) $value, true) : null;

                if (is_array($json) && is_string($alteradosAntigo[$key])) {
                    if (array_search($key, $keysToIgnore) !== false) {
                        continue;
                    }
                    $jsonOLD = json_decode((string) $alteradosAntigo[$key], true);
                    $out = array_merge($out, self::arrayDiff($json, $jsonOLD));
                } else {
                    $out[] = [
                        'field' => $key,
                        'old' => isset($alteradosAntigo[$key]) ? $alteradosAntigo[$key] : null,
                        'new' => $value
                    ];
                }
            }
        }

        return $out;
    }

    public static function httpsForce()
    {
        if ($_SERVER["HTTPS"] != "on") {
            header("Location: https://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
            die();
        }
    }
}
