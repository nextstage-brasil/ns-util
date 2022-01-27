<?php

require 'vendor/autoload.php';

if (strlen($argv[1]) === 0) {
    die("## Error: Informe a mensagem do commit: php git.php \"type/mensagem\"\n\n");
}
$message = $argv[1];

NsUtil\Package::git(__DIR__ . '/version', $message);