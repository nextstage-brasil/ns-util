<?php

namespace NsUtil;

use NsUtil\Helper;

class Translate
{
    private static $langs  = [];

    public static function getAll(string $lang): array
    {
        self::loadFile($lang);
        return self::$langs[$lang];
    }

    public static function get($key, $lang): string
    {
        self::loadFile($lang);
        $mykey = mb_strtolower($key);
        if (!isset(self::$langs[$lang][$mykey])) {
            self::addKey($key, $key, $lang);
        }

        return self::$langs[$lang][$mykey] ?? '';
    }

    private static function getFilename($lang)
    {
        $path = (getenv('TRANSLATE_PATH') ? getenv('TRANSLATE_PATH') : Helper::getPathApp() . '/lang')
            . '/nsutil-translate-files';
        $file = $path . '/' . $lang . '.json';

        if (!file_exists($file)) {
            Helper::saveFile($file, '', '{}');
            Helper::saveFile($path . '/.htaccess', '', 'require all denied', 'SOBREPOR');
        }

        return $file;
    }

    private static function loadFile($lang)
    {

        self::$langs[$lang] = json_decode(file_get_contents(
            self::getFilename($lang)
        ), true);
    }

    private static function updateFile($lang): void
    {
        if (is_writable(self::getFilename($lang))) {
            $ret = Helper::saveFile(
                self::getFilename($lang),
                '',
                json_encode(self::$langs[$lang], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'SOBREPOR'
            );
        }
    }

    private static function addKey($key, $value = null, $lang): void
    {
        $key = mb_strtolower($key);
        if ($value === null) {
            if (isset(self::$langs[$lang])) {
                unset(self::$langs[$lang][$key]);
            }
        } else {
            self::$langs[$lang][$key] = $value;
        }
        self::updateFile($lang);
    }
}
