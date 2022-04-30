<?php

namespace NsUtil;

/**
 * Verifica a compatibilidade do código PHP em referência a versão atual em questão (Lint)
 */
class Compatibility {

    public static function run($dir) {
        echo "### Check Lint on PHP Version: " . PHP_VERSION . PHP_EOL;
        self::check($dir);
        echo "\nFinished! (Only errors are displayed) \n";
    }

    private static function check($dir) {
        $files = DirectoryManipulation::openDir($dir);
        foreach ($files as $file) {
            $file = $dir . DIRECTORY_SEPARATOR . $file;
            if (is_dir($file)) {
                self::check($file);
                continue;
            } else {
                if (stripos($file, '.php') > 0) {
                    $ret = shell_exec('php -l ' . $file);
                    if (strpos($ret, 'No syntax errors detected') === false) {
                        echo $ret;
                    }
                }
            }
        }
        return true;
    }

}
