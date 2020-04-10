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

    public function __construct($host, $user, $pass, $port, $database) {
        $this->config = new stdClass();
        $this->config->host = $host;
        $this->config->port = $port;
        $this->config->database = $database;
        $this->config->user = $user;
        $this->config->pwd = $pass;
        $this->open();
    }

    // gravar o conector em session, vai garantir uma unica conexão no sistema todo, exceto se solicitado explicitamente new
    public function open() {
        if (!$this->con) {
            try {
                $stringConnection = "pgsql:host=" . $this->config->host . ";port=" . $this->config->port . ";dbname=" . $this->config->database . ";user=" . $this->config->user . ";password=" . $this->config->pwd;
                $this->con = new PDO($stringConnection);
                $this->con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (Exception $e) {
                echo '<p class="alert alert-error text-center">'
                . 'ERROR: Connection Failed (CPD-167)<br/>' . $e->getMessage()
                . '</p>';
                die();
            }
        }
    }

    public function getConn() {
        return $this->con;
    }

    public function close() {
        pg_close($this->con);
    }

    public function begin_transaction() {
        if (!self::$transaction_in_progress) {
            $this->executeQuery('START TRANSACTION');
            self::$transaction_in_progress = true;
            register_shutdown_function(array($this, "__shutdown_check"));
        }
    }

    public function __shutdown_check() {
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
    public function execQueryAndReturn($query, $log = true) {
        $this->open();
        $out = [];
        $this->executeQuery($query, $log);
        while ($dd = $this->next()) {
            $out[] = Helper::name2CamelCase($dd);
        }
        return $out;
    }

}
