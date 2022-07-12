<?php

namespace NsUtil;

use Exception;
use NsUtil\Helper;
use PDO;
use stdClass;

class ConnectionPostgreSQL {

    private $con; // garantir o singleton
    private $config;
    public $query;
    public $result;
    public $numRows;
    public $error;
    public $dd;
    public $lastInsertId;
    private static $transaction_in_progress;
    private $nullas = '';

    public function __construct($host, $user, $pass, $port, $database) {
        $this->config = new stdClass();
        $this->config->host = $host;
        $this->config->port = $port;
        $this->config->database = $database;
        $this->config->user = $user;
        $this->config->pwd = $pass;
        $this->open();
    }

    public function open() {
        if (!$this->con) {
            try {
                $stringConnection = "pgsql:host=" . $this->config->host . ";port=" . $this->config->port . ";dbname=" . $this->config->database . ";user=" . $this->config->user . ";password=" . $this->config->pwd;
                $this->con = new PDO($stringConnection);
                $this->con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (Exception $e) {
                throw new Exception($e->getMessage());
            }
        }
    }

    public function getConn() {
        return $this->con;
    }

    public function close() {
        $this->con = null;
    }

    public function begin_transaction() {
        if (!self::$transaction_in_progress) {
            $this->executeQuery('START TRANSACTION');
            self::$transaction_in_progress = true;
            register_shutdown_function(array($this, "__shutdown_check"));
        }
    }

    public function __shutdown_check() {
        $this->con = null;
        if (self::$transaction_in_progress) {
            $this->rollback();
        }
    }

    public function commit() {
        $this->executeQuery("COMMIT");
        self::$transaction_in_progress = false;
    }

    public function rollback() {
        $this->executeQuery("ROLLBACK");
        self::$transaction_in_progress = false;
    }

    public function autocommit($boolean) {
        $this->con->autocommit($boolean);
    }

    public function executeQuery($query) {
        $this->open();
        $res = false;
        $this->numRows = 0;
        $this->result = false;
        $this->error = false;
        $this->query = $query;

        try {
            $this->result = $this->con->prepare($query);
            if (!$this->result->execute()) {
                $this->error = $this->result->errorInfo()[2];
                throw new Exception($this->result->errorInfo()[0] . $this->result->errorInfo()[2], 0);
            }
            $this->numRows = $this->result->rowCount();
        } catch (Exception $exc) {
            $this->result = false;
            $this->result = false;
            if (stripos($exc->getMessage(), 'ERROR:  function unaccent') > -1) {
                die('DEV:  A EXTENSÃO UNNACCENT NÃO FOI INSTALADA');
            }
            throw new Exception($exc->getMessage());
        }
        return $res;
    }

    public function next() {
        try {
            if ($this->result) {
                $dados = $this->result->fetch(PDO::FETCH_ASSOC);
                if (is_array($dados)) {
                    return $dados;
                } else {
                    $this->result = false;
                    return false;
                }
            }
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Executa e retonar a query formatada com nameCase
     * @param type $query
     * @param type $log
     * @return type
     */
    public function execQueryAndReturn($query, $log = true, $keyCamelCaseFormat = true) {
        $this->open();
        $out = [];
        $this->executeQuery($query, $log);
        while ($dd = $this->next()) {
            if ($keyCamelCaseFormat) {
                $dd = Helper::name2CamelCase($dd);
            }
            $out[] = $dd;
        }
        return $out;
    }

    /**
     * Define o que será utilizado em nullas ao executar o insertByCopy
     * @param type $nullas
     */
    public function setNullAs($nullAs = '') {
        $this->nullas = $nullAs;
    }

    /**
     *
     * @param Connection $db
     * @param string $this->tableName
     * @param string[] $fields List of fields names.
     * @param array[] $records Two-demension array of cells (array of rows).
     * @return boolean
     */
    public function insertByCopy($toTable, array $fields, array $records) {
        $this->open();
        static $delimiter = "\t";
        $nullAs = $this->nullas;
        $rows = [];
        foreach ($records as $record) {
            $row = [];
            foreach ($fields as $key => $field) {
                $record[$key] = $record[$key] ? $record[$key] : null; //] array_key_exists($field, $record) ? $record[$field] : null;
                if (is_null($record[$key])) {
                    $record[$key] = $nullAs;
                } elseif (is_bool($record[$key])) {
                    $record[$key] = $record[$key] ? 't' : 'f';
                }

                $record[$key] = str_replace($delimiter, ' ', $record[$key]);
                // Convert multiline text to one line.
                $record[$key] = addcslashes($record[$key], "\0..\37");
                $row[] = $record[$key];
            }
            $rows[] = implode($delimiter, $row) . "\n";
        }

        $this->con->pgsqlCopyFromArray($toTable, $rows, $delimiter, addslashes($nullAs), implode(',', $fields));
        unset($rows);

        return true;
    }

    public function queryRunWithLoader($querys, $label, $showQueryOnLabel = false, $showQtde = false) {
        $loader = new StatusLoader(count($querys), $label);
        $loader->done(1);
        $loader->setShowQtde($showQtde);
        for ($i = 0; $i < count($querys); $i++) {
            if ($showQueryOnLabel) {
                $loader->setLabel($querys[$i]);
            }
            $this->executeQuery($querys[$i]);
            $loader->done($i + 1);
        }
        return $loader->getLastStatusBar();
    }

    /**
     * Executara um update na tabela com prepared. Os nomes do campos já devem estar no formato da tabela, sem camelcase
     * @param type $table
     * @param type $array
     * @param type $cpoWhere
     * @return boolean
     * @throws SistemaException
     */
    public function insert($table, $array, $nomeCpoId, $onConflict = '') {
        $preValues = $update = $valores = [];
        foreach ($array as $key => $value) {
            $keys[] = '"'.$key.'"';
            $preValues[] = '?';
            $valores[] = $value;
        }
        $query = "INSERT INTO $table (" . implode(',', $keys) . ") VALUES (" . implode(',', $preValues) . ")"
                . " $onConflict "
                . " returning $nomeCpoId as nsnovoid";
        $this->open();
        $res = false;
        $this->numRows = 0;
        $this->result = false;
        $this->error = false;
        try {
            $this->result = $this->con->prepare($query);
            if (!$this->result->execute($valores)) {
                $this->error = $this->result->errorInfo()[2];
                throw new Exception($this->result->errorInfo()[0] . $this->result->errorInfo()[2], 0);
            }
            return $res;
        } catch (Exception $exc) {
            $this->result = false;
            throw new Exception($exc->getMessage() . $query);
        }
    }

    /**
     * Executara um update na tabela com prepared. Os nomes do campos já devem estar no formato da tabela, sem camelcase
     * @param type $table
     * @param type $array
     * @param type $cpoWhere
     * @return boolean
     * @throws SistemaException
     */
    public function update($table, $array, $cpoWhere) {
        $update = $valores = [];
        $idWhere = $array[$cpoWhere];
        unset($array[$cpoWhere]);
        foreach ($array as $key => $value) {
            $valores[] = $value;
            $update[] = "\"$key\"=?";
        }
        // where
        $valores[] = $idWhere;
        $query = "update $table set " . implode(',', $update) . " where $cpoWhere=?";
        $this->open();
        $res = false;
        $this->numRows = 0;
        $this->result = false;
        $this->error = false;
        try {
            $this->result = $this->con->prepare($query);
            if (!$this->result->execute($valores)) {
                $this->error = $this->result->errorInfo()[2];
                throw new Exception($this->result->errorInfo()[0] . $this->result->errorInfo()[2], 0);
            }
            return $res;
        } catch (Exception $exc) {
            $this->result = false;
            throw new Exception($exc->getMessage() . $query);
        }
    }

}
