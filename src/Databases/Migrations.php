<?php

/**
 * A execução irá ler um arquivo SQL e ira rodar e executar cada instrução se não existir na tabela de controle das migrations
 */

namespace NsUtil\Databases;

use Exception;
use NsUtil\ConnectionPostgreSQL;
use NsUtil\DirectoryManipulation;
use NsUtil\Helper;

class Migrations {

    private $con, $sqlFilePath;

    public function __construct(string $path, ConnectionPostgreSQL $con) {
        $this->sqlFilePath = $path;
        $this->con = $con;
        $this->con instanceof ConnectionPostgreSQL;
        Helper::directorySeparator($this->sqlFilePath);

        // Criar diretorio se não existir
        if (!is_dir($this->sqlFilePath)) {
            Helper::mkdir($path);
        }

        // Criar tabela se não existir
        $this->con->executeQuery("CREATE SCHEMA IF NOT EXISTS _dbupdater");
        $sql = 'CREATE TABLE IF NOT EXISTS _dbupdater._migrations (name text, hash text NULL,created_at timestamp NULL DEFAULT now())';
        $this->con->executeQuery($sql);
    }

    private function getHash(string $string): string {
        return \hash('sha256', $string);
    }

    public function create(string $name, string $sql): Migrations {
        // Verificar se já existe
        $files = DirectoryManipulation::openDir($this->sqlFilePath);
        $exists = false;
        foreach ($files as $file) {
            if (stripos($file, $name) !== false) {
                $exists = true;
            }
        }
        if (!$exists) {
            $filename = $this->sqlFilePath . DIRECTORY_SEPARATOR . date('ymdHi') . '_' . $name . '.nsUtilDB';
            file_put_contents($filename, $sql);
            echo "\n$name was successfully created!";
        }
        return $this;
    }

    public function update(): array {
        $files = DirectoryManipulation::openDir($this->sqlFilePath);
        $this->con->begin_transaction();
        $loader = new \NsUtil\StatusLoader(count($files), 'Migrations');
        $loader->setShowQtde(true);
        $done = 0;
        foreach ($files as $file) {
            // somente arquivos desta classe
            if (stripos($file, '.nsUtilDB') !== false) {

                // Verificar se a instrução já foi executada
                $parts = explode('_', $file);
                array_shift($parts);
                $hash = $this->getHash(implode('_', $parts));

                if ((int) $this->con->execQueryAndReturn("select count(*) as qtde from _dbupdater._migrations where hash= '$hash'")[0]['qtde'] > 0) {
                    continue;
                }

                // Executar instrução e registrar na tabela
                $sql = file_get_contents($this->sqlFilePath . DIRECTORY_SEPARATOR . $file);
                try {
                    $this->con->executeQuery($sql);
                    $this->con->executeQuery("INSERT INTO _dbupdater._migrations (name,hash) VALUES ('$file', '$hash')");
                } catch (Exception $exc) {
                    $this->con->rollback();
                    return ['error' => $exc->getMessage(), 'details' => $exc->getTraceAsString()];
                }
            }
            $done++;
            $loader->done($done);
        }
        $this->con->commit();
        return ['error' => false];
    }

}
