<?php

namespace NsUtil;

class Password {

    /**
     * Metodo que codifica senha com token e criptografia
     * Constante TOKEN definida em _config.php
     * @param unknown_type $senha
     */
    public static function codificaSenha(string $senha): string {
        self::addToken($senha);
        return password_hash($senha, PASSWORD_DEFAULT);
    }

    public static function geraNovaSenha(): string {
        $senha = substr(md5((string) microtime()), 0, 12);
        return $senha;
    }

    public static function verify(string $senha, string $hash): bool {
        self::addToken($senha);
        return password_verify($senha, $hash);
    }

    private static function addToken(string &$senha, string $token = ''): void {
        $senha = md5((string) trim($senha) . Config::getData('token')); // incluir o token da aplicação
    }

    public static function forcaSenha(string $senha): int {
        $len = strlen($senha);
        if ($len < 6) {
            return -1;
        }
        $count = 0;
        if ($len >= 8) {
            $count++;
        }
        $array = array("[[a-z]]", "[[A-Z]]", "[[0-9]]", "[!#_-]");
        foreach ($array as $a) { // a cada interação positiva, um ponto adicionado
            if (preg_match($a, $senha)) {
                $count++;
            }
        }
        return $count;
    }

}
