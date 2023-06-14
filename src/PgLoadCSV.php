<?php

namespace NsUtil;

use stdClass;

class PgLoadCSV {

    private $file, $run, $consoleTable;
    private $csv = [];

    /**
     * 
     * @param \NsUtil\ConnectionPostgreSQL $con
     * @param type $schema
     * @param bool $schemaDrop
     * @param type $truncateTables
     */
    public function __construct(ConnectionPostgreSQL $con, $schema = 'import', bool $schemaDrop = false, $truncateTables = false) {
        //$this->file = realpath($file);
        $this->run = new stdClass();
        $this->run->con = $con;
        $this->run->schema = \NsUtil\Helper::sanitize($schema);
        $this->run->delimiter = "\t"; // delimiter to load
        $this->run->nullAs = ''; // null as to load
        $this->run->schemaDrop = $schemaDrop;
        $this->run->truncate = $truncateTables;

        $this->run->schema = $this->sanitizeField($schema);
        if ($this->run->schemaDrop) {
            $this->run->con->executeQuery("DROP SCHEMA IF EXISTS " . $this->run->schema . ' CASCADE');
        }
        $this->run->con->executeQuery("CREATE SCHEMA IF NOT EXISTS " . $this->run->schema);

        $this->consoleTable = new \NsUtil\ConsoleTable();
        $this->consoleTable->setHeaders(['Schema', 'Tabela', 'Linhas', 'Resultado']);
        $this->csv[] = ['Schema', 'Tabela', 'Linhas', 'Tamanho', 'Resultado'];
    }

    public function getConsoleTable() {
        $this->consoleTable instanceof ConsoleTable;
        return $this->consoleTable;
    }

    public function getCsv() {
        return $this->csv;
    }

    private function consoleTableAddLine($qtdeLinhas, $filesize, $message) {
        $this->csv[$this->file] = [$this->run->schema, $this->run->table, $qtdeLinhas, $filesize, $message];
        $this->consoleTable->addRow([$this->run->schema, $this->run->table, $qtdeLinhas, $message]);
    }

    /**
     * Define o que será utilizado em nullas ao executar o insertByCopy
     * @param type $nullas
     */
    public function setNullAs($nullas = '') {
        $this->run->con->setNullAs($nullas);
        return $this;
    }

    /**
     * 
     * @param string $file_or_dir Diretorio ou CSV que deve ser ingerido
     * @param string $tablename - Caso false, será utilizado o nome do arquivo CSV sanitizado
     * @return boolean
     */
    public function run(string $file_or_dir, $tablename = false) {
        if (is_dir($file_or_dir)) {
            $types = array('csv', 'xlsx');
            $dir = realpath($file_or_dir);
            if ($handle = opendir($dir)) {
                while ($entry = readdir($handle)) {
                    $ext = strtolower(pathinfo($entry, PATHINFO_EXTENSION));
                    if (in_array($ext, $types)) {
                        $file = $dir . '/' . $entry;




                        // Caso seja xlsx, converter para CSV usando lib python
                        if ($ext === 'xlsx') {
                            $ret = shell_exec('type xlsx2csv');
                            if (stripos($ret, 'not found') > -1) {
                                die("### ATENÇÃO ### \nBiblioteca xlsx2csv não esta instalada. "
                                        . "\nPara continuar, execute: 'sudo apt-get update && sudo apt-get install -y xlsx2csv'"
                                        . "\n"
                                        . "###############"
                                        . "\n\n\n");
                            }
                            $t = explode(DIRECTORY_SEPARATOR, $file);
                            $csv = '/tmp/' . str_replace('.xlsx', '.csv', array_pop($t));
                            $csv_error = shell_exec("xlsx2csv $file $csv");
                            if (!file_exists($csv)) {
                                die('Ocorreu erro ao converter arquivo XLSX para CSV: ' . $csv_error);
                            }
                            $file = $csv;
                        }


                        echo "-----------------------------------------------------------" . PHP_EOL;
                        $this->run($file, $tablename);
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
        Helper::fileConvertToUtf8($file_or_dir);
        $t = explode(DIRECTORY_SEPARATOR, $this->file);

        $tbl = str_replace('.csv', '', array_pop($t));
        $this->run->table = $tablename ? $tablename : $this->sanitizeField(str_replace(['.csv', '.', '-'], ['', '_', '_'], Helper::sanitize($tbl)));

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
        if (stripos($data[0], "\t") > 0) {
            $this->run->explode = "\t";
        }

        // Remover BOM
        $data[0] = str_replace("\xEF\xBB\xBF", '', $data[0]);


        $head = explode($this->run->explode, $data[0]);
        $this->run->fields = $cpos = $control = [];

        foreach ($head as $key => $val) {
            $val = $this->sanitizeField($val);

            // termos exclusivos do postgres
            $termosReservados = ['references', 'if', 'else', 'case', 'desc', 'asc'];
            if (array_search($val, $termosReservados) !== false) {
                $val = '_' . $val;
            }

            if (strlen((string)$val) === 0) {
                continue;
            }
            $cpos[] = "$val text null";
            if (isset($this->run->fields[md5((string)$val)])) {
                $control[md5((string)$val)]++;
                $val = $val . '_' . $control[md5((string)$val)];
            } else {
                $control[md5((string)$val)] = 1;
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
        $this->run->filesize = filesize($this->file);
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
            $this->consoleTableAddLine($this->run->linhas, $this->run->filesize, $loader->getLastStatusBar());

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
        //$str = preg_replace("/[^A-Za-z0-9]/", "_", $str);

        $str = trim(str_replace('"', '', $str));
        $from = "áàãâéêíóôõúüçÁÀÃÂÉÊÍÓÔÕÚÜÇ";
        $to = "aaaaeeiooouucAAAAEEIOOOUUC";
        $keys = array();
        $values = array();
        preg_match_all('/./u', $from, $keys);
        preg_match_all('/./u', $to, $values);
        $mapping = array_combine($keys[0], $values[0]);
        $str = strtr($str, $mapping);
        $str = preg_replace("/[^A-Za-z0-9]/", "_", $str);

        if (is_numeric($str[0])) {
            $str = '_' . $str;
        }
        return $str;
    }

}
