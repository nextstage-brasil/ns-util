<?php

namespace NsUtil;

use ZipArchive;

class Log {

    public function __construct() {
    }

    /**
     * 
     * @param string $file Path absoluto do arquivo
     * @param int $maxSize Em MB, tamanho máximo do arquivo para rotacionar
     * @return string
     */
    public static function rotate(string $file, int $maxSize = 10, ?string $newPathPrefix = 'default'): void {
        Helper::directorySeparator($file);
        $parts = explode(DIRECTORY_SEPARATOR, $file);
        $filename = array_pop($parts);
        $path = implode(DIRECTORY_SEPARATOR, $parts);
        Helper::mkdir($path);
        $pathPrefix = $newPathPrefix === 'default' ? str_replace('.', '-', $filename . '-rotated') : $newPathPrefix;
        $filenameFull = $path . DIRECTORY_SEPARATOR . $filename;
        if (is_file($filenameFull) && filesize($filenameFull) > 1024 * 1024 * $maxSize) {
            $zipname = $filenameFull . '.' . \date('ymdHis') . '.zip';
            $zip = new ZipArchive();
            $zip->open($zipname, ZipArchive::CREATE);
            $zip->addFile($filenameFull);
            $zip->close();
            if (file_exists($zipname)) {
                unlink($filenameFull);
            }
            if (null !== $newPathPrefix) {
                $parts = explode(DIRECTORY_SEPARATOR, $zipname);
                $filename = array_pop($parts);
                $newPath = implode(DIRECTORY_SEPARATOR, $parts)
                    . DIRECTORY_SEPARATOR
                    . $pathPrefix;
                Helper::mkdir($newPath);
                $newFilename = $newPath
                    . DIRECTORY_SEPARATOR
                    . $filename;
                rename($zipname, $newFilename);
            }
        }
    }

    public static function logTxt(string $file, string $message): void {
        if (is_array($message) || is_object($message)) {
            $message = var_export($message, true);
        }

        $origem = array_map(function ($item) {
            if (is_null($item) || !is_array($item) || strlen((string) $item['class']) === 0) {
                return '';
            }
            $f = explode(DIRECTORY_SEPARATOR, $item['file']);
            return array_pop($f)
                . '::'
                . $item['line']
                . ' > '
                . $item['class']
                . '::' . $item['function']
                . '()';
        }, debug_backtrace());
        $message .= "\n\t" . implode("\n\t", $origem);

        // criação do diretorio caso não existe
        self::rotate($file);
        $fp = fopen($file, "a");
        $date_message = "[" . date('d/m/Y H:i:s') . "] " . $message . PHP_EOL;
        fwrite($fp, $date_message);
        fclose($fp);
    }
}
