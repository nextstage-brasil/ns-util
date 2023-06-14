<?php

require ('../../vendor/autoload.php');

// Argumentos
$tipo = $argv[1];
$dados = json_decode(base64_decode($argv[2]), true);

switch ($tipo) {
    case 'pgLoadCSV':
        /*
          $dadosEsperados = [
          'config' => [],
          'schema' => '',
          'schemaDrop' => '',
          'truncateTables' => '',
          'file' => ''
          ];
         */
        $con = new \NsUtil\ConnectionPostgreSQL($dados['config']['host'], $dados['config']['user'], $dados['config']['pass'], $dados['config']['port'], $dados['config']['database']);
        $loader = new NsUtil\PgLoadCSV($con, $dados['schema'], $dados['schemaDrop'], $dados['truncateTables']);
        $loader->run($dados['file']);
        break;
    default:
        die('Action não definida');
}