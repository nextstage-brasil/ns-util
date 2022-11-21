<?php

namespace NsUtil;

use Exception;
use NsUtil\Connection\SQLite;

class UniqueExecution {

    private $con, $ref;

    public function __construct(string $dbName = 'defaultApplication', string $pathToDB = '/tmp') {
        $user = posix_getpwuid(posix_geteuid())['name'];
        $pathToDB = (($pathToDB === '/tmp') ? Helper::getTmpDir() : $pathToDB);
        $db = $pathToDB . '/' . 'NSUniqueExecution';
        $this->con = new SQLite($db);
        $this->ref = $dbName;
        $this->createDB();
        // date_default_timezone_set('UTC');

        if ($user !== 'root')   {
            @chmod($db, 0777);
            @chown($db, 'root');
            // @chgrp($db, 'root');
        }

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

    /**
     * Retorna mensagem padrão contendo a data de inicio do processo atual
     * @return string
     */
    public function getDefaultMessageIsRunning() : string {
        $init = $this->getStartedAt();
        return 'There is another proccess running. Started at ' . date('c', $init);
    }

}
