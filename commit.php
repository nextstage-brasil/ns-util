<?php

require 'vendor/autoload.php';

$message = (($argv[1]) ? $argv[1] : "version/version");
$git = NsUtil\Package::setVersion(__DIR__ . '/version', $message, 0, 0, 1);

foreach ($git['git'] as $cmd) {
    shell_exec($cmd);
}

