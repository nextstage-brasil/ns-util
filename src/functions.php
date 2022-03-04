<?php

namespace NsUtil;

function ns_nextstage(): void {
    echo "\n### NsUtil functions is loaded! ### \n";
}

/**
 * JsonDecode, com tratamento das regras aplicadas no >php8
 * @param type $json
 * @param type $assoc
 * @param type $depth
 * @param type $options
 * @return type
 */
function json_decode($json, bool $assoc = false, int $depth = 512, int $options = 0) {
    switch (true) {
        case is_null($json):
            $data = \json_decode([], $assoc, $depth, $options);
            break;
        case is_array($json):
        case is_object($json):
            $data = $json;
            break;
        default:
            $data = \json_decode($json, $assoc, $depth, $options);
            break;
    }
    return $data;
}

/**
 * Para manter compatibilidade sem typagem na chamada (legado)
 * @param type $delimiter
 * @param type $string
 */
function explode($delimiter, $string) {
    explode((string) $delimiter, (string) $string);
}
