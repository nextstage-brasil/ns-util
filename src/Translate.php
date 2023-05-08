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
        if (!isset(self::$langs[$lang][$key])) {
            self::addKey($key, $key, $lang);
        }

        return self::$langs[$lang][$key] ?? '';
    }

    private static function loadFile($lang)
    {
        $path = (getenv('TRANSLATE_PATH') ? getenv('TRANSLATE_PATH') : Helper::getPathApp() . '/lang')
            . '/nsutil-translate-files';
        $file = $path . '/' . $lang . '.json';

        if (!file_exists($file)) {
            Helper::saveFile($file, '', '{}');
            Helper::saveFile($path . '/.htaccess', '', 'require all denied', 'SOBREPOR');
        }

        self::$langs[$lang] = json_decode(file_get_contents($file), true);
    }

    private static function updateFile($lang): void
    {
        $path = getenv('TRANSLATE_PATH');
        $file = $path . '/' . $lang . '.json';
        self::$langs[$lang] = Helper::saveFile(
            $file,
            '',
            json_encode(self::$langs[$lang], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'SOBREPOR'
        );
    }

    private static function addKey($key, $value = null, $lang): void
    {
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
