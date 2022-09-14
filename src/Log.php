<?php

namespace NsUtil;

use ZipArchive;

class Log {

    public function __construct() {
        
    }

    /**
     * 
     * @param string $path
     * @param string $file
     * @param int $maxSize Em MB, tamanho máximo do arquivo para rotacionar
     * @return string
     */
    public static function rotate(string $path, string $file, int $maxSize=10): string {
        $filename = $path . DIRECTORY_SEPARATOR . $file;
        if (is_file($filename) && filesize($filename) > 1024 * 1024 * $maxSize) {
            $zipname = $filename . '.' . \date('ymdHis') . '.zip';
            $zip = new ZipArchive();
            $zip->open($zipname, ZipArchive::CREATE);
            $zip->addFile($filename);
            $zip->close();
            if (file_exists($zipname)) {
                unlink($filename);
            }
        }
        return $filename;
    }

    public static function logTxt(string $file, string $message): void {
        if (is_array($message) || is_object($message)) {
            $message = var_export($message, true);
        }
        $b = debug_backtrace();
        $origem = ';-- {ORIGEM: ' . ((isset($b[1])) ? $b[1]['class'] : '') . '::' . ((isset($b[1])) ? $b[1]['function'] : '') . ':' . $b[0]['line'] . '}';

        // criação do diretorio caso não existe
        Helper::directorySeparator($file);
        $parts = explode(DIRECTORY_SEPARATOR, $file);
        $filename = array_pop($parts);
        $path = implode(DIRECTORY_SEPARATOR, $parts);
        Helper::mkdir($path);
        $filelog = self::rotate($path, $filename);
        $fp = fopen($filelog, "a");
        $date_message = "[" . date('d/m/Y H:i:s') . "]" . $message . $origem . "\r\n";
        fwrite($fp, $date_message);
        fclose($fp);
    }

}
