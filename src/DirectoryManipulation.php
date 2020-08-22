<?php

namespace NsUtil;

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
                if (is_dir($path) and ( $file != ".") and ( $file != "..")) {
                    $this->getSizeOf($path);
                } else if (is_file($path)) {
                    //echo $path . PHP_EOL;
                    $t = explode('.', $file);
                    $type = mb_strtolower(array_pop($t));
                    if (!isset($this->total[$type])) {
                        $this->total[$type]['count'] = 0;
                        $this->total[$type]['size'] = 0;
                    }
                    $this->total[$type]['count'] ++;
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

}

/** Exemplode uso: 
$dir = new DirectoryManipulation();
$dir->getSizeOf($argv[1]);
$print = '';
foreach ($dir->total as $key => $val) {
    $size = (int) $val['size'];
    $ext = 'bytes';
    if ($size > 1000) {
        $ext = 'KB';
        $size = $size / 1024;
        if ($size > 1000) {
            $ext = 'MB';
            $size = $size / 1024;
        }
        if ($size > 1000) {
            $size = $size / 1024;
            $ext = 'GB';
        }
    }

    $size = number_format($size, 0, ',', '.') . $ext;

    $print .= "Tipo: ." . $key
            . PHP_EOL
            . "\tArquivos: " . $val['count']
            . "\n\tTamanho: " . $size
            . PHP_EOL
    ;
}
echo $print;

file_put_contents(__DIR__ . '/contadir.txt', $print);
**/
