<?php

namespace NsUtil;

use stdClass;

class PgLoadingCSV {

    private $file, $handle, $return, $run;

    public function __construct($file, ConnectionPostgreSQL $con, $schema = 'import') {
        $this->file = realpath($file);
        $this->return = new stdClass();
        $this->run = new stdClass();
        $this->run->con = $con;
        $this->run->schema = \NsUtil\Helper::sanitize($schema);
        $this->run->delimiter = "\t"; // delimiter to load
        $this->run->nullAs = ''; // null as to load
        //$this->run->memory_limit = preg_replace("/[^0-9]/", "", ini_get('memory_limit')); // * 1024 * 1024;

        $this->return->error = false;
        if (!file_exists($this->file)) {
            $this->return->error = 'File not exists';
            die($this->return->error);
        }
        $t = explode(DIRECTORY_SEPARATOR, $this->file);
        $this->run->table = str_replace(['.csv', '.', '-'], ['', '_', '_'], \NsUtil\Helper::sanitize(array_pop($t)));
        $this->run->tableSchema = $this->run->schema . '.' . $this->run->table;
        $this->run->con->executeQuery("CREATE SCHEMA IF NOT EXISTS " . $this->run->schema);
        $this->head();
        $this->run();
    }

    private function head() {
        $fh = fopen($this->file, "rb") or die('not open');
        $data = fgetcsv($fh, 1000, '\\');
        fclose($fh);
        $this->run->explode = ',';
        if (stripos($data[0], ';') > 0) {
            $this->run->explode = ';';
        }
        $head = explode($this->run->explode, $data[0]);
        $this->run->fields = $cpos = [];
        foreach ($head as $key => $val) {
            //$key = str_replace(['"', "'"], [''], $key);
            //$val = str_replace(['"', "'", ' '], ['', '', '_'], $val);
            $val = $this->sanitizeField($val);
            $cpos[] = "$val text null";
            $this->run->fields[] = $val;
        }
        $this->run->fields = implode(',', $this->run->fields);
        $this->run->con->executeQuery("CREATE TABLE IF NOT EXISTS "
                . $this->run->tableSchema
                . " ("
                . implode(',', $cpos)
                . ")");
    }

    private function run() {
        echo 'Tabela: "' . $this->run->tableSchema . '"';
        $this->run->linhas = \NsUtil\Helper::linhasEmArquivo($this->file);
        echo ' com ' . number_format($this->run->linhas, 0, ',', '.') . ' linhas' . PHP_EOL;
        $loader = new StatusLoader($this->run->linhas, $this->run->table, 40);
        $loader->setShowQtde(false);
        $setLoader = 0;
        $row = $qtdeLinhas = $setLoader = 0;
        $start = microtime(true);
        $con = $this->run->con->getConn();
        $this->run->con->begin_transaction();

        if (($handle = fopen($this->file, "r")) !== false) {
            do {
                // impressao do loader
                $qtdeLinhas++;
                $setLoader++;

                if ($row <= 0) { // ignorar a primeira linha?
                    $line = $this->getLines($handle);
                    $row++;
                    continue;
                }

                // Decidir sobre descarregar em disco
                if ($setLoader > 3000) {
                    $setLoader = 0;
                    $memoriaAlocada = (int) round(memory_get_usage(true), 0);
                    $loader->done($qtdeLinhas - 5); // - 5 para deixar a ultima chamada para o final da operação
                    $loader->setLabel('R/Seg:'
                            . number_format(round($qtdeLinhas / (microtime(true) - $start), 0), 0, ',', '.')
                            . ' Mem: ' . round($memoriaAlocada / 1048576, 0) . 'MB'
                    );
                    //. " Mem:" . round(($memoriaAlocada / $this->run->memory_limit * 100), 2) . '%');
                    // insert on table
                    //$descarga++;
                    if (count($records) > 0) {
                        $con->pgsqlCopyFromArray($this->run->tableSchema, $records, $this->run->delimiter, addslashes($this->run->nullAs), $this->run->fields);
                        $records = [];
                    }
                }

                // Obter dados do arquivos
                $line = $this->getLines($handle);
                if (!$line) {
                    continue;
                } else {
                    $records[] = $line; //19===un_hash
                }
            } while ($qtdeLinhas <= $this->run->linhas);



            fclose($handle);

            if (count($records) > 0) {
                $loader->setLabel('Ingerindo dados finais');
                $con->pgsqlCopyFromArray($this->run->tableSchema, $records, $this->run->delimiter, addslashes($this->run->nullAs), $this->run->fields);
                $records = [];
            }
            $loader->setLabel('OK! R/Seg:'
                    . number_format(round($qtdeLinhas / (microtime(true) - $start), 0), 0, ',', '.')
            );

            $loader->done($this->run->linhas); // por causa do inicio em 0
            $this->run->con->commit();
        }
    }

    // le a linha no ponteiro e retorna preparado para ingestao
    private function getLines($handle) {
        $data = \NsUtil\Helper::myFGetsCsv($handle, $this->run->explode);
        if ($data === false || $data === null) {
            return false;
        }

        // tratamento para insert on copy
        foreach ($data as $key => $val) {
            if (is_null($data[$key])) {
                $data[$key] = $this->run->nullAs;
            } elseif (is_bool($data[$key])) {
                $data[$key] = $data[$key] ? 't' : 'f';
            }
            $data[$key] = str_replace($this->run->delimiter, ' ', $data[$key]);
            // Convert multiline text to one line.
            $data[$key] = addcslashes($data[$key], "\0..\37");
        }
        return implode($this->run->delimiter, $data);
    }

    private function sanitizeField($str) {
        return preg_replace("/[^A-Za-z]/", "_", $str);
    }

    function pgInsertByCopy(PDO $db, $tableName, array $fields, array $records) {
        static $delimiter = "\t", $nullAs = '\\N';

        $rows = [];

        foreach ($records as $record) {
            $record = array_map(
                    function ($field) use( $record, $delimiter, $nullAs) {
                $value = array_key_exists($field, $record) ? $record[$field] : null;

                if (is_null($value)) {
                    $value = $nullAs;
                } elseif (is_bool($value)) {
                    $value = $value ? 't' : 'f';
                }

                $value = str_replace($delimiter, ' ', $value);
                // Convert multiline text to one line.
                $value = addcslashes($value, "\0..\37");

                return $value;
            }, $fields);
            $rows[] = implode($delimiter, $record) . "\n";
        }

        return $db->pgsqlCopyFromArray($tableName, $rows, $delimiter, addslashes($nullAs), implode(',', $fields));
    }

}
