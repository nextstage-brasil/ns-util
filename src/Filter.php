<?php

namespace NsUtil;

class Filter {

    public function __construct() {
        
    }

    /**
     * Reproduz o comportamento do depreacted FILTER_SANITIZE_STRING
     * @param type $string
     * @return type
     */
    public static function string(string $string): string {
        $str = preg_replace('/\x00|<[^>]*>?/', '', $string);
        return str_replace(["'", '"'], ['&#39;', '&#34;'], $str);
    }

    public static function integer(mixed $string): int {
        return (int) filter_var($string, FILTER_VALIDATE_INT);
    }
    
}
