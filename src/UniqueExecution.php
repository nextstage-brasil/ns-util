<?php

namespace NsUtil;

use Exception;
use NsUtil\Connection\SQLite;

class UniqueExecution
{

    private $con, $ref;

    public function __construct(string $dbName = 'defaultApplication', string $pathToDB = '/tmp')
    {
        $defaultConnection = getenv('UNIQUE_EXECUTION_DRIVER') ? getenv('UNIQUE_EXECUTION_DRIVER') : 'sqlite';

        switch ($defaultConnection) {
            case 'psql':
                $this->con = ConnectionPostgreSQL::getConnectionByEnv();
                break;
            //sqlite
            default:
                $user = posix_getpwuid(posix_geteuid())['name'];
                $pathToDB = (($pathToDB === '/tmp') ? Helper::getTmpDir() : $pathToDB);
                $db = $pathToDB . '/' . 'NSUniqueExecution';
                $this->con = new SQLite($db);
                if ($user !== 'root') {
                    @chmod($db, 0777);
                }
                break;
        }

        $this->ref = $dbName;

        $this->createDB();
    }

    public static function create(string $dbName = 'defaultApplication', int $secondsLimit = 3600): UniqueExecution
    {
        $ret = new UniqueExecution($dbName);
        $ret->start($secondsLimit, true);
        return $ret;
    }

    // Cria a tabela necessária para execução
    private function createDB(): void
    {
        $query = 'CREATE TABLE IF NOT EXISTS "_execution_lock" (
            "ref" TEXT PRIMARY KEY,
            "inited_at" INTEGER
        )';
        try {
            $this->con->executeQuery($query);
        } catch (Exception $exc) {
            throw new Exception('UNIQUE EXECUTION ERROR - CONNECT DB: ' . $exc->getMessage());
        }
    }

    /**
     * Verifica se uma execução esta em processamento
     *
     * @param integer $timeLimit
     * @param boolean $throwException
     * @return boolean
     */
    public function isRunning(int $timeLimit = 3600, bool $throwException = false): bool
    {
        $query = 'SELECT COUNT(*) AS counter FROM "_execution_lock" WHERE ref = :ref AND inited_at > :timeLimit';
        $counter = $this->con->execQueryAndReturnPrepared($query, [
            'ref' => $this->ref,
            'timeLimit' => time() - $timeLimit
        ])[0]['counter'];

        $isRunning = $counter > 0;
        if ($isRunning && $throwException) {
            throw new Exception($this->getDefaultMessageIsRunning());
        }
        return $isRunning;
    }

    /**
     * Registra o inicio do processo unico a ser controlado
     *
     * @param integer $timeTocheckAnotherExecution
     * @param boolean $throwExceptionIfIsRunning
     * @return self
     */
    public function start(int $timeTocheckAnotherExecution = 3600, bool $throwExceptionIfIsRunning = false)
    {
        $this->isRunning($timeTocheckAnotherExecution, $throwExceptionIfIsRunning);

        $query = "INSERT INTO \"_execution_lock\" (ref, inited_at) VALUES(?, ?) ON CONFLICT(ref) DO UPDATE SET inited_at = ?";

        $initedAt = time();

        $this->con->executeQuery($query, [$this->ref, $initedAt, $initedAt]);

        return $this;
    }

    /**
     * Registra o encerramento do processo unico
     * @return void
     */
    public function end(): void
    {
        $query = 'DELETE FROM "_execution_lock" WHERE ref=?';
        $this->con->executeQuery($query, [$this->ref]);
    }

    /**
     * Retorna o timestamp do inicio do processo em execução, ou 0 caso nenhum esteja em processamento
     * @return int
     */
    public function getStartedAt(): int
    {
        $out = 0;
        $query = 'SELECT * FROM "_execution_lock" WHERE ref=?';
        $list = $this->con->execQueryAndReturnPrepared($query, [$this->ref]);
        if (count($list) > 0) {
            $out = (int) $list[0]['initedAt'];
        }
        return $out;
    }

    /**
     * Retorna mensagem padrão contendo a data de inicio do processo atual
     * @return string
     */
    public function getDefaultMessageIsRunning(): string
    {
        $init = $this->getStartedAt();
        return 'There is another proccess running. Started at ' . date('c', $init);
    }
}
