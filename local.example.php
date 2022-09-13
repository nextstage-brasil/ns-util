<?php

require './vendor/autoload.php';

$pathDefault = realpath(__DIR__ . '/../');
$LOCAL_PROJECTS = [
    $pathDefault . '/my-application',
];
$src = realpath(__DIR__) . DIRECTORY_SEPARATOR . 'src';
$vendor = 'nextstage-brasil/ns-util';
(new \NsUtil\LocalComposer())($src, $LOCAL_PROJECTS, $vendor);
