<?php

namespace NsUtil;

class ErrorHandler {

    private $filename, $applicationName, $printError;

    function __construct(string $log_filename, string $applicationName, bool $printError = false) {
        Helper::directorySeparator($log_filename);
        $this->filename = $log_filename;
        $this->applicationName = $applicationName;
        $this->printError = $printError;
        set_error_handler([&$this, 'userErrorHandler']);
    }

    function userErrorHandler($errno, $errmsg, $filename, $linenum) {
        $log_file_error = $this->filename;

        // Rotate File
        if (file_exists($this->filename)) {
            if (filesize($this->filename) >= 1046000) {
                rename($this->filename, $this->filename . '_' . date('YmdHis') . '.old');
            }
        }

        if (!file_exists($this->filename)) {
            Helper::saveFile($this->filename, false, "appname,time,filename,line_num,error,message,backtrace");
        }

        // Ignore notice
        if (8 !== (int) $errno) {
            $time = date("d M Y H:i:s");
            // Get the error type from the error number 
            $errortype = array(1 => "Error",
                2 => "Warning",
                4 => "Parsing Error",
                8 => "Notice",
                16 => "Core Error",
                32 => "Core Warning",
                64 => "Compile Error",
                128 => "Compile Warning",
                256 => "User Error",
                512 => "User Warning",
                1024 => "User Notice",
                8192 => 'Deprecated');
            $errlevel = $errortype[$errno];

            //Write error to log file (CSV format) 
            $errmsg = str_replace('"', '\"', (string) $errmsg);
            $filename = str_replace('\\', '/', $filename);
            $backtrace = debug_backtrace();
            $toLog = $bt = [];
            foreach ($backtrace as $item) {
                if (isset($item['file'])) {
                    $tmp = explode(DIRECTORY_SEPARATOR, $item['file']);
                    $file = array_pop($tmp);
                    $prefix = array_pop($tmp);
                    $bt[] = "$prefix/$file::" . $item['line'];
                    $toLog[] = $item['file'] . '::' . $item['line'];
                }
            }
            $bt_string = implode(' | ', $bt);
            $appname = $this->applicationName;
            file_put_contents($log_file_error, PHP_EOL . "\"$appname\", \"$time\",\"$filename\",\"$linenum\",\"$errlevel\", \"$errmsg\", \"$bt_string\"", FILE_APPEND);

            // Warning não mata aplicação, os demais sim
            if ($errno != 2) {
                $isWeb = isset($_SERVER['HTTP_HOST']);
                $enter = (($isWeb) ? '<br/>' : PHP_EOL);
                echo "A fatal error has occurred. Script execution has been aborted" . $enter;
                if ($this->printError) {
                    echo "### $errlevel: $errmsg" . $enter;
                    echo implode($enter, $toLog);
                    echo $enter;
                }
                die();
            }
        }
    }

}
