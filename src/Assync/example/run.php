<?php

use NsUtil\Assync\Assync;

$autoload = __DIR__ . '/vendor/autoload.php';
require $autoload;

$assync = (new Assync())
    ->setParallelProccess(100)
    ->setShowLoader('Teste')
    ->setAutoloader($autoload)
    ->setLogfile(__DIR__ . '/test.log');

// Adicionar closures
$proccess = 100000;
$rand = 10000000;
for ($i = 0; $i < $proccess; $i++) {
    $assync->addClosure('teste', function () use ($rand) {
        $x = 0;
        for ($i = 0; $i < $rand; $i++) {
            $x += $i;
        }
        return "21-Finished: $x | RAND: $rand";
    });
}

// Run
$assync->run();
