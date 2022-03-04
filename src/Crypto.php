<?php

namespace NsUtil;

use Exception;
use function mb_substr;

class Crypto {

    private $chave;

    public function __construct($chave) {
        if (strlen((string) $chave) < 16) {
            throw new Exception('NsCrypto: Atenção: chave com menos de 16 caracteres considerada insegura');
        }
        $this->chave = $chave;

        if (!in_array('sodium', get_loaded_extensions())) {
            echo 'NSUTIL ERROR: Libsodium IS NOT installed (CR19)';
            die();
        }
    }

    public function encrypt($string, $chaveExtra = '') {
        return $this->crypto72('encrypt', $string, $chaveExtra);
    }

    public function decrypt($string, $chaveExtra = '') {
        return $this->crypto72('decrypt', $string, $chaveExtra);
    }

    public function getHash($string) {
        return \hash('sha256', $string . $this->chave);
    }

    private function crypto72($action, $string, $chaveExtra = '') {
        if (strlen((string) $string) === 0) {
            return '';
        }
        $Chave = pack('H*', \hash('sha256', $this->chave . $chaveExtra));

        $output = '';
        if ($action === 'encrypt') {
            $IV = random_bytes(SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_IETF_NPUBBYTES);
            $output = base64_encode($IV . sodium_crypto_aead_chacha20poly1305_ietf_encrypt($string, '', $IV, $Chave));
        } else if ($action === 'decrypt') {
            $Resultado = base64_decode($string);
            $t2 = mb_substr((string) $Resultado, SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_IETF_NPUBBYTES, null, '8bit');
            $IV2 = mb_substr((string) $Resultado, 0, SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_IETF_NPUBBYTES, '8bit');
            if (strlen((string) $t2) > 0 && strlen((string) $IV2) > 0) {
                $output = sodium_crypto_aead_chacha20poly1305_ietf_decrypt($t2, '', $IV2, $Chave);
            }
        } else {
            $output = '';
        }
        return $output;
    }

    /**
     * Retorna um item cryptografado ou aberto (conforme $action) utilizando regra simples de AES-256-CBC com openssl_encrypt
     * 
     * Interessante uso para necessidades menores, pois é mais rápido e gera uma string menor
     * @param type $action enum: 'encrypt' or 'decrypt'
     * @param type $string
     * @param type $chave
     * @return type
     */
    public function simple(string $action, string $string, string $chave = null) {
        $output = false;

        $encrypt_method = "AES-256-CBC";
        if ($chave) {
            $secret_key = md5((string) $this->chave . $chave);
        } else {
            $secret_key = $this->chave;
        }
        $secret_iv = $secret_key . '_IV';

        // hash
        $key = hash('sha256', $secret_key);

        // iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
        $iv = substr(hash('sha256', $secret_iv), 0, 16);

        if ($action == 'encrypt') {
            $output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
            $output = base64_encode($output);
        } else if ($action == 'decrypt') {
            $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
        } else {
            die('Invalid Type (NSCrypto-84)');
        }

        return $output;
    }

}
