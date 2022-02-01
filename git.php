<?php

require 'vendor/autoload.php';

if (strlen($argv[1])===0){
    die ("Mensagem do commit é requerido");
}

NsUtil\Package::git(__DIR__ . '/version', $argv[1]);