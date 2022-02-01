<?php

namespace NsUtil\Connection;

use Exception;
use NsUtil\Helper;
use PDO;
use stdClass;

class SQLite {

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

    public function __construct($filename) {
        $this->config = new stdClass();
        $this->config->filename = $filename;
        $this->open();
    }

    public function open() {
        if (!$this->con) {
            try {
                $stringConnection = 'sqlite:' . $this->config->filename;
                $this->con = new PDO($stringConnection);
                $this->con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->con->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
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
            $this->executeQuery('BEGIN');
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
                die('DEV:  A EXTESÃO UNNACCENT NÃO FOI INSTALADA');
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
     * Executara um update na tabela com prepared. Os nomes do campos já devem estar no formato da tabela, sem camelcase
     * @param type $table
     * @param type $array
     * @param type $cpoWhere
     * @return boolean
     * @throws SistemaException
     */
    public function insert($table, $array, $nomeCpoId, $onConflict = null) {
        $preValues = $update = $valores = [];
        foreach ($array as $key => $value) {
            $keys[] = $key;
            $preValues[] = '?';
            $valores[] = $value;
        }
        $query = "INSERT INTO $table (" . implode(',', $keys) . ") VALUES (" . implode(',', $preValues) . ")"
        . ((isset($onConflict)) ? " $onConflict " : "")
        //. " returning $nomeCpoId as nsnovoid"
        ;
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
            // Obter last ID
            $query = 'select seq as nsnovoid from sqlite_sequence where name="' . $table . '"';
            $res = $this->execQueryAndReturn($query)[0];
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
            $update[] = "$key=?";
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
