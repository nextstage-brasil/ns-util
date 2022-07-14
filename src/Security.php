<?php

namespace NsUtil;

class Security {

    /**
     * 
     * @param type $URL_BASE: URL base da aplicação
     */
    public function __construct() {
        
    }

    private static function addSessionToKey(&$chave) {
        $ip = session_id();
        $chave = hash('md5', $ip . date('Ymd') . $chave);
    }

    public static function getUrlOfFileInDisc($filepath) {
        $env = getenv('DOCKER_PHP_FILES_DIR');
        $root = ((null !== $env) ? $env : $_SERVER['DOCUMENT_ROOT']);
        $dr = str_replace("\\", '/', realpath($root));
        $dir = str_replace("\\", '/', $filepath);
        $path = str_replace($dr, '', $dir);
        
        return $_SERVER['REQUEST_SCHEME'] .'://'. $_SERVER['HTTP_HOST'] . $path;
    }

    public static function includeJSFromUrl(array $js) {
        foreach ($js as $item) {
            $include[] = "'$item'";
        }
        $var = "[" . implode(', ', $include) . "].forEach(function (val) {
                    //console.log(val);
                    document.write(unescape(\"%3Cscript src='\" +val + \"' type='text/javascript'%3E%3C/script%3E\"));
                });
                ";

        return Packer::jsPack($var);
    }

    public static function getFingerprintJS($chave) {
        self::addSessionToKey($chave);
        $crypto = self::includeJSFromUrl([
                    self::getUrlOfFileInDisc(__DIR__ . '/lib/js/crypto.js'),
                    self::getUrlOfFileInDisc(__DIR__ . '/lib/js/fingerprint_util.js'),
        ]);
        $iv = substr((string) hash('md5', $chave . '_IV'), 0, 16);
        $js = ""
                . "var _NSC118= '$chave';"
                . "var _NSIV = '$iv';"
                //. "console.log('$chave'); console.log('$iv');"
                . file_get_contents(__DIR__ . '/lib/js/fingerprint.js')
                . file_get_contents(__DIR__ . '/lib/js/mycrypto.js')
        ;
        $js = \NsUtil\Packer::jsPack($js);
        return $crypto . $js;
    }

    /**
     * 
     * @param type $var
     * @param type $chave
     * @return typeDecripta uma variavel criada pela funcao JS, MyCripto()

      public static function decryptFromJS($var, $chave) {
      self::addSessionToKey($chave);
      $iv_1 = substr((string)hash('sha256', $chave . '_IV'), 0, 16);
      $key = pack('H*', $chave);
      $iv = pack('H*', $iv_1);
      $decrypted = openssl_decrypt(base64_decode($var), "AES-256-CBC", $key, OPENSSL_RAW_DATA, $iv);
      return json_decode($decrypted);
      }
     */

    /**
     * Decrypt data from a CryptoJS json encoding string
     *
     * @param mixed $passphrase
     * @param mixed $jsonString
     * @return mixed
     */
    static function cryptoJsAesDecrypt($passphrase, $jsonString) {
        self::addSessionToKey($passphrase);
        $jsondata = json_decode($jsonString, true);
        $salt = hex2bin($jsondata["s"]);
        $ct = base64_decode($jsondata["ct"]);
        $iv = hex2bin($jsondata["iv"]);
        $concatedPassphrase = $passphrase . $salt;
        $md5 = array();
        $md5[0] = md5((string) $concatedPassphrase, true);
        $result = $md5[0];
        for ($i = 1; $i < 3; $i++) {
            $md5[$i] = md5((string) $md5[$i - 1] . $concatedPassphrase, true);
            $result .= $md5[$i];
        }
        $key = substr((string) $result, 0, 32);
        $data = openssl_decrypt($ct, 'aes-256-cbc', $key, true, $iv);
        return json_decode($data, true);
    }

    /**
     * Encrypt value to a cryptojs compatiable json encoding string
     *
     * @param mixed $passphrase
     * @param mixed $value
     * @return string
     */
    static function cryptoJsAesEncrypt($passphrase, $value) {
        self::addSessionToKey($passphrase);
        $salt = openssl_random_pseudo_bytes(8);
        $salted = '';
        $dx = '';
        while (strlen((string) $salted) < 48) {
            $dx = md5((string) $dx . $passphrase . $salt, true);
            $salted .= $dx;
        }
        $key = substr((string) $salted, 0, 32);
        $iv = substr((string) $salted, 32, 16);
        $encrypted_data = openssl_encrypt(json_encode($value), 'aes-256-cbc', $key, true, $iv);
        $data = array("ct" => base64_encode($encrypted_data), "iv" => bin2hex($iv), "s" => bin2hex($salt));
        return json_encode($data);
    }

}
