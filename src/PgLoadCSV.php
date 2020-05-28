<?php

namespace NsUtil;

use stdClass;

class PgLoadCSV {

    private $file, $run;

    public function __construct(ConnectionPostgreSQL $con, $schema = 'import', bool $schemaDrop = false, $truncateTables = false) {
        //$this->file = realpath($file);
        $this->run = new stdClass();
        $this->run->con = $con;
        $this->run->schema = \NsUtil\Helper::sanitize($schema);
        $this->run->delimiter = "\t"; // delimiter to load
        $this->run->nullAs = ''; // null as to load
        $this->run->schemaDrop = $schemaDrop;
        $this->run->truncate = $truncateTables;

        $this->run->schema = $schema;
        if ($this->run->schemaDrop) {
            $this->run->con->executeQuery("DROP SCHEMA IF EXISTS " . $this->run->schema . ' CASCADE');
        }
        $this->run->con->executeQuery("CREATE SCHEMA IF NOT EXISTS " . $this->run->schema);
    }

    public function run(string $file_or_dir) {
        if (is_dir($file_or_dir)) {
            $types = array('csv');
            $dir = realpath($file_or_dir);
            if ($handle = opendir($dir)) {
                while ($entry = readdir($handle)) {
                    $ext = strtolower(pathinfo($entry, PATHINFO_EXTENSION));
                    if (in_array($ext, $types)) {
                        $file = $dir . '/' . $entry;
                        echo "-----------------------------------------------------------" . PHP_EOL;
                        $this->run($file);
                        echo PHP_EOL;
                    }
                }
                closedir($handle);
            }
            echo "-----------------------------------------------------------" . PHP_EOL;
            return true;
        }
        $this->file = realpath($file_or_dir);
        if (!file_exists($this->file)) {
            echo "File '$file' not exists" . PHP_EOL;
        }
        $t = explode(DIRECTORY_SEPARATOR, $this->file);
        $this->run->table = $this->sanitizeField(str_replace(['.csv', '.', '-'], ['', '_', '_'], \NsUtil\Helper::sanitize(array_pop($t))));
        $this->run->tableSchema = $this->run->schema . '.' . $this->run->table;
        $this->head();
        $this->execute();
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
        $this->run->fields = $cpos = $control = [];

        foreach ($head as $key => $val) {
            $val = $this->sanitizeField($val);
            $cpos[] = "$val text null";
            if ($this->run->fields[md5($val)]) {
                $control[md5($val)] ++;
                $val = $val . '_' . $control[md5($val)];
            } else {
                $control[md5($val)] = 1;
            }
            $this->run->fields[] = $val;
        }

        //$this->run->fields = implode(',', $this->run->fields);
        $this->run->con->executeQuery("CREATE TABLE IF NOT EXISTS "
                . $this->run->tableSchema
                . " ("
                . implode(',', $cpos)
                . ")");
        if ($this->run->truncate) {
            $this->run->con->executeQuery("TRUNCATE TABLE " . $this->run->tableSchema . " CASCADE");
        }
    }

    private function execute() {
        echo 'Tabela: "' . $this->run->tableSchema . '"';
        $this->run->linhas = \NsUtil\Helper::linhasEmArquivo($this->file);
        echo ' com ' . number_format(($this->run->linhas - 1), 0, ',', '.') . ' linhas a verificar' . PHP_EOL;
        $loader = new StatusLoader($this->run->linhas, $this->run->table);
        $loader->setShowQtde(false);
        $setLoader = 0;
        $row = $qtdeLinhas = $setLoader = 0;
        $start = microtime(true);
        //$con = $this->run->con->getConn();
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
                    // descarga
                    if (count($records) > 0) {
                        $this->run->con->insertByCopy($this->run->tableSchema, $this->run->fields, $records);
                        $records = [];
                    }
                }

                // Obter dados do arquivos
                $line = $this->getLines($handle);
                if (!$line) {
                    continue;
                } else {
                    $records[] = $line;
                }
            } while ($qtdeLinhas <= $this->run->linhas);
            fclose($handle);

            if (count($records) > 0) {
                $loader->setLabel('Ingerindo dados finais');
                $this->run->con->insertByCopy($this->run->tableSchema, $this->run->fields, $records);
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
        return $data;
    }

    private function sanitizeField($str) {
        $str = preg_replace("/[^A-Za-z0-9]/", "_", $str);
        if (is_numeric($str[0])) {
            $str = '_' . $str;
        }
        return $str;
    }

}
