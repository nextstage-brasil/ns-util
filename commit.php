<?php

require 'vendor/autoload.php';

$message = (($argv[1]) ? $argv[1] : "version/version");
NsUtil\Package::git(__DIR__ . '/version', $message, 0, 0, 1);