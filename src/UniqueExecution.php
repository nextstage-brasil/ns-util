<?php

namespace NsUtil;

use Exception;
use NsUtil\Connection\SQLite;

class UniqueExecution {

    private $con, $ref;

    public function __construct(string $dbName='defaultApplication', string $pathToDB = '/tmp') {
        $this->con = new SQLite($pathToDB . '/' . 'NSUniqueExecution');
        $this->ref = $dbName;
        $this->createDB();
        date_default_timezone_set('UTC');
    }

    // Cria a tabela necessária para execução
    private function createDB(): void {
        $query = 'CREATE TABLE IF NOT EXISTS "execution" (
            "ref" TEXT PRIMARY KEY,
            "inited_at" INTEGER
        )';
        try {
            $this->con->executeQuery($query);
        } catch (Exception $exc) {
            echo $exc->getMessage();
            die();
        }
    }

    /**
     * Tempo limite de execução considerado "travado"
     * @param type $timeLimit
     * @return type
     */
    public function isRunning(int $timeLimit = 3600): bool {
        $query = 'SELECT COUNT(*) AS counter FROM "execution" WHERE ref="' . $this->ref . '" AND inited_at > ' . (time() - $timeLimit);
        $counter = $this->con->execQueryAndReturn($query)[0]['counter'];
        return $counter > 0;
    }

    /**
     * Registra o inicio do processo unico a ser controlado
     * @return void
     */
    public function start(): void {
        $query = "INSERT INTO execution(ref, inited_at) VALUES('" . $this->ref . "', " . time() . ")
                  ON CONFLICT(ref) DO UPDATE SET inited_at=" . time();
        $this->con->executeQuery($query);
    }

    /**
     * Registra o encerramento do processo unico
     * @return void
     */
    public function end(): void {
        $query = "DELETE FROM \"execution\" WHERE ref='" . $this->ref . "'";
        $this->con->executeQuery($query);
    }

    /**
     * Retorna o timestamp do inicio do processo em execução, ou 0 caso nenhum esteja em processamento
     * @return int
     */
    public function getStartedAt(): int {
        $out = 0;
        $query = 'SELECT * FROM "execution" WHERE ref="' . $this->ref . '"';
        $list = $this->con->execQueryAndReturn($query);
        if (count($list) > 0) {
            $out = (int) $list[0]['initedAt'];
        }
        return $out;
    }

}