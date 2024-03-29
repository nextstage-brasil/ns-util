<?php

/**
 * A execução irá ler um arquivo SQL e ira rodar e executar cada instrução se não existir na tabela de controle das migrations
 */

namespace NsUtil\Databases;

use Exception;
use NsUtil\Config;
use NsUtil\ConnectionPostgreSQL;
use NsUtil\DirectoryManipulation;
use NsUtil\Helper;
use NsUtil\StatusLoader;

class Migrations
{

    private $con, $sqlFilePath;

    public function __construct(string $path, ConnectionPostgreSQL $con)
    {
        $this->sqlFilePath = $path;
        $this->con = $con;
        $this->con instanceof ConnectionPostgreSQL;
        Helper::directorySeparator($this->sqlFilePath);

        // Criar diretorio se não existir
        if (!is_dir($this->sqlFilePath)) {
            Helper::mkdir($path);
        }

        // HTAccess de proteção do apache
        Helper::saveFile($this->sqlFilePath . '/.htaccess', false, "Require all denied", 'SOBREPOR');

        // Criar tabela se não existir
        $this->con->executeQuery("CREATE SCHEMA IF NOT EXISTS _dbupdater");
        $sql = 'CREATE TABLE IF NOT EXISTS _dbupdater._migrations (name text, hash text NULL,created_at timestamp NULL DEFAULT now())';
        $this->con->executeQuery($sql);

        // Criação da função basica de execução
        $this->con->executeQuery("CREATE OR REPLACE PROCEDURE public.ns_ddl_execute(query text) LANGUAGE plpgsql AS
                                            \$procedure\$
                                                    begin
                                                            execute query;
                                                    END;
                                            \$procedure\$;");
    }

    private function getHash(string $string): string
    {
        return \hash('sha256', $string);
    }

    public function create(string $name, string $sql, bool $includeDate = true): Migrations
    {
        // Verificar se já existe
        $files = DirectoryManipulation::openDir($this->sqlFilePath);
        asort($files);
        $exists = false;
        $name = str_replace(' ', '-', Helper::sanitize($name));
        foreach ($files as $file) {
            if (stripos($file, $name) !== false) {
                $exists = true;
            }
        }
        if (!$exists) {
            $filename = $this->sqlFilePath . DIRECTORY_SEPARATOR . (($includeDate) ? date('ymdHi') . '_' : '') . $name . '.nsUtilDB';
            file_put_contents($filename, $sql);
            chmod($filename, 0777);
            // echo "\n$name was successfully created!";
        }
        return $this;
    }

    public function createByArray(array $list): Migrations
    {
        $counter = 1;
        foreach ($list as $title => $sql) {
            $prefix = str_pad((string) $counter, 4, '0', STR_PAD_LEFT);
            if (is_file($sql)) {
                $sql = $this->getContentBySQLFileToNsDDLExecute($sql);
            }
            $this->create($prefix . '-' . $title, $sql);
            $counter++;
        }
        return $this;
    }

    public function getContentBySQLFileToNsDDLExecute($filepath): string
    {
        if (!file_exists($filepath)) {
            die("File not exists: $filepath");
        }
        $content = file_get_contents($filepath);
        return "call ns_ddl_execute('" . str_replace("'", "''", $content) . "')";
    }

    public function update(): array
    {
        $files = DirectoryManipulation::openDir($this->sqlFilePath);
        asort($files);
        $this->con->begin_transaction();
        $loader = new StatusLoader(count($files), 'Migrations');
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
                    $done++;
                    $loader->done($done);
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

    public static function builder(string $pathAplicacao, array $arrayMigrations, array $conPreQuerys = [])
    {
        $config = new Config(getenv());

        $env = (Helper::fileSearchRecursive('.env', $pathAplicacao));
        if ($env) {
            $config->loadEnvFile($env);
        }

        $con = new ConnectionPostgreSQL($config->get('DBHOST'), $config->get('DBUSER'), $config->get('DBPASS'), $config->get('DBPORT'), $config->get('DBNAME'));
        foreach ($conPreQuerys as $q) {
            $con->executeQuery($q);
        }

        $migrations = (new Migrations($pathAplicacao . '/_migrations', $con))
            ->createByArray($arrayMigrations)
            ->update();

        return $migrations;
    }

    public static function run(string $pathAplicacao, array $conPreQuerys = [], bool $removeDirAfterSuccess = false): array
    {
        try {
            $dirMigrations = \realpath($pathAplicacao) . '/_migrations';
            $migrations = ['error' => "Path is not found $dirMigrations"];
            if (is_dir($dirMigrations)) {
                ob_start();
                $config = new Config(getenv());
                $inifile = Helper::fileSearchRecursive('.env', $pathAplicacao);
                if (file_exists($inifile)) {
                    $config->loadEnvFile($inifile);
                }
                $con = new ConnectionPostgreSQL($config->get('DBHOST'), $config->get('DBUSER'), $config->get('DBPASS'), $config->get('DBPORT'), $config->get('DBNAME'));
                $migrations = (new Migrations($pathAplicacao . '/_migrations', $con))
                    ->update();
                if ($migrations['error'] === false && $removeDirAfterSuccess) {
                    Helper::deleteDir($dirMigrations);
                    if (is_dir($dirMigrations)) {
                        rename($dirMigrations, __DIR__ . '/../../_____rem');
                    }
                }
                ob_end_clean();
            }
        } catch (Exception $exc) {
            throw new Exception($exc->getMessage());
        }

        return $migrations;
    }

    public static function loadFromPath(string $path): array
    {
        if (!is_dir($path)) {
            throw new Exception("Path '$path' is not a directory");
        }
        $files = DirectoryManipulation::openDir($path);
        asort($files);
        $migrations = [];
        foreach ($files as $file) {
            $sql = "$path/$file";
            Helper::directorySeparator($sql);
            $migrations[$file] = $sql;
        }
        return $migrations;
    }
}
