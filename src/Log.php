<?php

namespace NsUtil;

use ZipArchive;

class Log
{

    public function __construct()
    {
    }

    public static function getDefaultPathNSUtil()
    {
        $defaultPathNSUtil = '/var/log/ns-util';
        return is_dir($defaultPathNSUtil)
            ? $defaultPathNSUtil
            : '/tmp';
    }

    /**
     * 
     * @param string $file Path absoluto do arquivo
     * @param int $maxSize Em MB, tamanho máximo do arquivo para rotacionar
     * @return string
     */
    public static function rotate(string $file, int $maxSize = 10, ?string $newPathPrefix = 'default'): void
    {
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

    /**
     * Registra um log de texto em um arquivo determinado
     *
     * @param string $file
     * @param string|array $message
     * @param boolean $ignoreBacktrace
     * @return void
     */
    public static function logTxt(string $file, $message, bool $ignoreBacktrace = false): void
    {
        if (is_array($message) || is_object($message)) {
            $message = var_export($message, true);
        }

        if (!$ignoreBacktrace) {
            $message .= "\n\t" . implode("\n\t", self::getBacktrace());
        }

        // criação do diretorio caso não existe
        self::rotate($file);
        $fp = fopen($file, "a");
        $date_message = "[" . gmdate('c') . "] " . $message . PHP_EOL;
        fwrite($fp, $date_message);
        fclose($fp);
    }

    /**
     * imprimir na tela o texto
     */
    public static function see($var, $html = true, $backtraceShow = true): void
    {
        switch (true) {
            case is_object($var):
                $out = json_encode(Helper::objectPHP2Array($var), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                break;
            case is_array($var):
                $out = json_encode($var, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                break;
            case is_bool($var):
                $out = $var ? 'TRUE' : 'FALSE';
                break;
            case is_string($var) && $var === '':
                $out = 'Variavel não possui nenhum valor';
                break;
            default:
                $out = $var;
                break;
        }

        $out = "<hr/>
        <h5>Log visualization:</h5>
        <pre>$out</pre>
        <h5>Backtrace</h5>
        <pre>" . implode("\n\t", self::getBacktrace()) . "
        </pre>
        <hr/>
        ";



        echo !$html
            ? Helper::filterSanitize($out)
            : $out;
    }

    /**
     * Retorna o backtrace até aqui
     *
     * @return array
     */
    public static function getBacktrace()
    {
        $origem = array_map(function ($item) {
            if (is_null($item) || !is_array($item) || strlen((string) $item['class']) === 0) {
                return '';
            }
            $item['file'] = $item['file'] ? $item['file'] : '';
            return $item['file']
                . ' : '
                . $item['line']
                . ' > '
                . $item['class']
                . '::' . $item['function']
                . '()';
        }, debug_backtrace());

        return $origem;
    }
}
