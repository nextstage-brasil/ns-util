<?php

namespace NsUtil;

use Exception;
use finfo;

class File {


    public function __construct() {
    }

    /**
     * Retorna o mime do arquivo
     *
     * @param string $file
     * @param boolean $encoding
     * @return string
     */
    public static function getMimeType(string $file, $encoding = true): string {
        if (!file_exists($file)) {
            throw new Exception("File $file not exists");
        }
        if (!function_exists('finfo_open')) {
            throw new Exception("finfo_open function is not enable");
        }

        if (is_file($file) && is_readable($file)) {
            $finfo = new finfo($encoding ? FILEINFO_MIME : FILEINFO_MIME_TYPE);
            $out = explode(';', $finfo->file($file))[0];
        } else {
            throw new Exception("File $file readabled");
        }

        return str_replace('.', '-', $out);
    }
}
