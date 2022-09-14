<?php

namespace NsUtil;

class Log {

    public function __construct() {
        
    }

    private static function rotate(string $path, string $file): string {
        $filename = $path . DIRECTORY_SEPARATOR . $file;
        if (is_file($filename) && filesize($filename) > 1046000) {
            $zipname = $filename . '.' . \date('ymdHis') . '.zip';
            $zip = new \ZipArchive();
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
        $origem = ';-- {ORIGEM: ' . $b[1]['class'] . '::' . $b[1]['function'] . ':' . $b[0]['line'] . '}';

        // criação do diretoiro caso não existe
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
