<?php

require ('../../../vendor/autoload.php');

use NsUtil\Assync\Assync;
use NsUtil\Eficiencia;

$n = 10; // número de processos (atenção ao número de processadores disponíveis e memória
$bg = new Assync($n);
$ef = new Eficiencia();
$cmd = 'php ' . __DIR__ . '/process.php';
$bg->add($cmd)
        ->add($cmd)
        ->add($cmd)
        ->add($cmd)
        ->add($cmd)
        ->add($cmd)
        ->add($cmd)
        ->add($cmd)
        ->add($cmd)
        ->add($cmd)
        ->add($cmd)
        ->add($cmd)
        ->add($cmd)
        ->add($cmd)
        ->add($cmd)
        ->add($cmd)
        ->add($cmd)
        ->add($cmd)
;

$bg->run();
echo "Resultado com $n paralelos: " . $ef->end()->text . PHP_EOL;



die();

/*
$bg2 = new NsUtil\Assync();
$command = 'php ' . __DIR__ . '/process.php';
$bg->execute($command);
sleep(1);
$bg2->execute($command);

while ($bg->isRunning() || $bg2->isRunning()) {
    echo "Processo A: " . (($bg->isRunning()) ? 'Rodando' : 'Finalizado') . PHP_EOL;
    echo "Processo B: " . (($bg2->isRunning()) ? 'Rodando' : 'Finalizado') . PHP_EOL;
    sleep(1);
}
  */