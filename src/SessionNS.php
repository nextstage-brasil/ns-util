<?php

namespace NsUtil;

class SessionNS {

    public function __construct() {
        
    }

    public static function get(string $key, string $keyB = null) {
        if (!is_null($keyB)) {
            $out = ( (isset($_SESSION[$key][$keyB])) ? $_SESSION[$key][$keyB] : null);
        } else {
            $out = ( (isset($_SESSION[$key])) ? $_SESSION[$key] : null);
        }
        // Garantia para sempre retornar inteiro pros IDs
        if (substr($key, 0, 2) === 'id' || substr((string) $keyB, 0, 2) === 'id') {
            $out = (int) is_null($out) ? 0 : $out;
        }
        return $out;
    }

    public static function set(string $key, $val = null, bool $merge = true): void {
        // Unset
        if (is_null($val)) {
            unset($_SESSION[$key]);
        }
        // Merge
        else if ($merge === true && is_array($val) && is_array($_SESSION[$key])) {
            $_SESSION[$key] = array_merge($_SESSION[$key], $val);
        }
        // Set
        else {
            $_SESSION[$key] = $val;
        }
        // Force INT do Ids
        $_SESSION = self::setIdToInt($_SESSION);
    }

    public static function clearAll() {
        foreach ($_SESSION as $key => $value) {
            self::set($key, null);
        }
        foreach (['user', 'login_vars', 'nav', 'xabagaia'] as $key) {
            self::set($key, null);
        }
    }

    /**
     * Garante que todas chaves "ids" serão do tipo INT
     * @param mixed $var
     */
    private static function setIdToInt($var) {
        foreach ($var as $key => $item) {
            if (is_array($item)) {
                $var[$key] = self::setIdToInt($item);
            } else {
                // Setar int
                if (substr($item, 0, 2) === 'id') {
                    $var[$key] = (int) $item;
                }
            }
        }
        return $var;
    }

}
