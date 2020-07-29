<?php

namespace NsUtil;

class Log {
    
    public function __construct() {
    }

    public static function logTxt($file, $message) {
        if (is_array($message) || is_object($message)) {
            $message = var_export($message, true);
        }
        $b = debug_backtrace();
        $origem = ';-- {ORIGEM: ' . $b[1]['class'] . '::' . $b[1]['function'] . ':' . $b[0]['line'] . '}';

        // criação do diretoiro caso não existe
        $filename = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $file);
        $dir = explode(DIRECTORY_SEPARATOR, $filename);
        array_pop($dir);
        Helper::mkdir(implode(DIRECTORY_SEPARATOR, $dir));
        $fp = fopen($filename, "a");
        $date_message = "[" . date('d/m/Y H:i:s') . "]" . $message . $origem . "\r\n";
        fwrite($fp, $date_message);
        fclose($fp);
    }


}
