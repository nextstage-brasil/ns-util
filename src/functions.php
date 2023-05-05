<?php

namespace NsUtil;

function ns_nextstage(): void
{
    echo "\n### NsUtil functions is loaded! ### \n";
}

/**
 * JsonDecode, com tratamento das regras aplicadas no >php8
 *
 * @param [type] $json
 * @param boolean $assoc
 * @param integer $depth
 * @param integer $options
 * @return mixed
 */
function json_decode($json, bool $assoc = false, int $depth = 512, int $options = 0)
{
    switch (true) {
        case is_null($json):
            $data = \json_decode('{}', $assoc, $depth, $options);
            break;
        case is_array($json):
        case is_object($json):
            $data = $json;
            break;
        default:
            $data = \json_decode($json, $assoc, $depth, $options);
            break;
    }
    return \json_decode(
        json_encode($data),
        $assoc,
        $depth,
        $options
    );
}

/**
 * Para manter compatibilidade sem typagem na chamada (legado)
 * @param string $delimiter
 * @param string $string
 */
function ns_explode($delimiter, $string)
{
    return explode((string) $delimiter, (string) $string);
}



if (!function_exists('encrypt')) {
    /**
     * Encrypt the given value.
     *
     * @param array|string $value
     * @param string $passphrase
     * @param string|null $extraKey
     * @return string
     */
    function encrypt($value, string $passphrase, string $extraKey = null): string
    {
        return (new Crypto($passphrase))->encrypt($value, $extraKey);
    }
}

if (!function_exists('dd')) {

    /**
     * Display and die
     *
     * @param mixed $var
     * @param boolean|null $isHtml
     * @param boolean $showBacktrace
     * @return void
     */

    function dd($var, ?bool $isHtml = null, bool $showBacktrace = true): void
    {
        echo Log::see($var, $isHtml, $showBacktrace);
        die();
    }
}

if (!function_exists('now')) {
    /**
     * Create a new Carbon instance for the current time.
     *
     * @param  string $tz
     * @return Date
     */
    function now($tz = null)
    {
        return new Date('NOW', $tz);
    }
}
