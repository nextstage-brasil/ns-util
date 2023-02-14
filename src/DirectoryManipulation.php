<?php

namespace NsUtil;

use Exception;

class DirectoryManipulation {

    public $total, $dir;

    public function __construct() {
        $this->total = [];
    }

    public function getSizeOf($dir) {
        if ($diretorio = opendir($dir)) {
            while (false !== ($file = readdir($diretorio))) {
                $path = $dir . DIRECTORY_SEPARATOR . $file;
                //echo $path; die();
                if (is_dir($path) and ($file != ".") and ($file != "..")) {
                    $this->getSizeOf($path);
                } else if (is_file($path)) {
                    //echo $path . PHP_EOL;
                    $t = explode('.', $file);
                    $type = mb_strtolower(array_pop($t));
                    if (!isset($this->total[$type])) {
                        $this->total[$type]['count'] = 0;
                        $this->total[$type]['size'] = 0;
                    }
                    $this->total[$type]['count']++;
                    $this->total[$type]['size'] += filesize($path);
                }
            }
            closedir($diretorio);
        }
    }

    public function getSize($dir, $type) {
        $this->getSizeOf($dir);
        return $this->total[mb_strtolower($type)];
    }

    /**
     * Le um diretório em disco e retorna os arquivos constantes ali. Inclusive nome dos diretórios
     * 
     * Importante: não é recursivo.
     * @param string $dir
     * @return array
     */
    public static function openDir($dir) {
        if (!is_dir($dir)) {
            throw new Exception("Parameter '$dir' is not a directory");
        }
        $out = [];
        if ($handle = opendir($dir)) {
            while (false !== ($file = readdir($handle))) {
                if ($file != "." && $file != ".." && stripos($file, '__NEW__') === false) {
                    $out[] = $file;
                }
            }
            closedir($handle);
        }
        return $out;
    }

    public static function recurseCopy($src, $dst) {
        $dir = opendir($src);
        while (false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                if (is_dir($src . '/' . $file)) {
                    self::recurseCopy($src . '/' . $file, $dst . '/' . $file);
                } else {
                    Helper::createTreeDir($dst . '/' . $file);
                    $origem = $src . '/' . $file;
                    $destino = $dst . '/' . $file;
                    Helper::directorySeparator($origem);
                    Helper::directorySeparator($destino);
                    if (!file_exists($origem)) {
                        echo "File $origem not found \n";
                    } else {
                        copy($origem, $destino);
                    }
                }
            }
        }
        closedir($dir);
    }


    /**
     * Realiza a remoção de todo conteúdo de um diretório. Não remove o diretório
     *
     * @param string $dir
     * @param integer $days
     * @return void
     */
    public static function clearDir(string $dir, int $days = 7) {
        $files = self::openDir($dir);
        $format = new Format();
        foreach ($files as $file) {
            $filename = $file;
            if (is_dir($filename)) {
                self::clearDir($filename, $days);
            } else {
                // Remove
                $createdAt = filemtime($filename);
                $removeAt = $format->setString(
                    $format->setString(time())->subDays($days)
                )->dateToMktime();

                // validate rule and remove
                $createdAt < $removeAt ? unlink($filename) : null;
            }
        }
    }

    public static function deleteDir($pasta) {
        Helper::directorySeparator($pasta);
        if (!is_dir($pasta)) {
            return true;
        }

        $iterator = new \RecursiveDirectoryIterator($pasta, \FilesystemIterator::SKIP_DOTS);
        $rec_iterator = new \RecursiveIteratorIterator($iterator, \RecursiveIteratorIterator::CHILD_FIRST);

        foreach ($rec_iterator as $file) {
            $file->isFile() ? unlink($file->getPathname()) : rmdir($file->getPathname());
        }

        rmdir($pasta);
        return is_dir($pasta);
    }
}