<?php

namespace NsUtil;

use Exception;
use function mb_substr;

class Crypto {

    private $chave;

    public function __construct($chave) {
        if (strlen($chave) < 16) {
            throw new Exception('NsCrypto: Atenção: chave com menos de 16 caracteres considerada insegura');
        }
        $this->chave = $chave;
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
        if (strlen($string) === 0) {
            return '';
        }
        $Chave = pack('H*', \hash('sha256', $this->chave . $chaveExtra));

        $output = '';
        if ($action === 'encrypt') {
            $IV = random_bytes(SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_IETF_NPUBBYTES);
            $output = base64_encode($IV . sodium_crypto_aead_chacha20poly1305_ietf_encrypt($string, '', $IV, $Chave));
        } else if ($action === 'decrypt') {
            $Resultado = base64_decode($string);
            $t2 = mb_substr($Resultado, SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_IETF_NPUBBYTES, null, '8bit');
            $IV2 = mb_substr($Resultado, 0, SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_IETF_NPUBBYTES, '8bit');
            if (strlen($t2) > 0 && strlen($IV2) > 0) {
                $output = sodium_crypto_aead_chacha20poly1305_ietf_decrypt($t2, '', $IV2, $Chave);
            }
        } else {
            $output = '';
        }
        return $output;
    }

}
