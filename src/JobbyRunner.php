<?php

namespace NsUtil;

use Closure;
use Exception;
use NsUtil\CrontabCheck;
use NsUtil\Helper;
use NsUtil\Log;
use NsUtil\UniqueExecution;
use Spatie\Async\Pool;

class JobbyRunner {

    private $logError;
    private $jobs = [];
    private $pool;
    private $printSuccessLog = false;

    public function __construct(string $pathToLog) {
        // logError
        $this->logError = $pathToLog;
        Helper::directorySeparator($this->logError);
        Helper::mkdir($this->logError);
        $this->logError .= DIRECTORY_SEPARATOR . 'NS_JobbyRunner.log';
        $this->pool = Pool::create();
        $this->pool instanceof Pool;

        if (Helper::getSO() === 'windows') {
            die('ERROR: This class not running on windows');
        }
        if (!$this->pool->isSupported()) {
            die('ERROR: Check the extensions: pcntle posix');
        }
    }

    public function setAutoload(string $path): JobbyRunner {
        $this->pool->autoload($path);
        return $this;
    }

    public function setConcurrency(int $concurrency): JobbyRunner {
        $this->pool->concurrency($concurrency);
        return $this;
    }

    public function setPrintSuccessLog(bool $printSuccessLog): JobbyRunner {
        $this->printSuccessLog = $printSuccessLog;
        return $this;
    }

    public function run(): array {
        // Executar os arquivos conforme regras
        $ja = [];

        $now = date('c');
        $printSuccessLog = $this->printSuccessLog;
        foreach ($this->jobs as $job) {
            list($name, $description, $maxTimeExecution, $schedule, $isEnable, $closure) = $job;
            $onlyOne = new UniqueExecution(md5(__FILE__ . $name));
            $out = [];
            $out[] = $name;
            $out[] = $schedule;
            $out[] = 'Enabled: ' . $isEnable ? 'true' : 'false';

            // Validação de execução
            switch (true) {
                case $onlyOne->isRunning($maxTimeExecution * 60):
                    $out[] = $onlyOne->getDefaultMessageIsRunning();
                    $closure = null;
                    break;
                case (!$isEnable):
                    $out[] = 'Não habilitado';
                    $closure = null;
                    break;
                case (!CrontabCheck::check($schedule)):
                    $out[] = 'Fora do prazo';
                    $closure = null;
                    break;
                default:
                    $out[] = 'Execução agendada';
                    break;
            }

            // Programar a closure conforme chamado
            if ($closure instanceof \Closure) {
                $this->pool->add(function () use ($name, $closure, $maxTimeExecution) {
                            $onlyOne = new UniqueExecution(md5(__FILE__ . $name));
                            if ($onlyOne->isRunning($maxTimeExecution * 60)) {
                                return $onlyOne->getDefaultMessageIsRunning();
                            } else {
                                $onlyOne->start();
                                $out = $closure();
                                $onlyOne->end();
                                return $out;
                            }
                        })
                        ->then(function ($output) use ($now, $name, $description, $printSuccessLog) {
                            if ($printSuccessLog) {
                                Log::logTxt($this->logError, "[$now] [$description] SUCCESS:  $output");
                            }
                            (new UniqueExecution(md5(__FILE__ . $name)))->end();
                        })
                        ->catch(function (Exception $exception) use ($now, $description) {
                            Log::logTxt($this->logError, "[$now] [$description] ERROR: " . $exception->getMessage());
                            (new UniqueExecution(md5(__FILE__ . $name)))->end();
                        });
            }
            $ja[] = implode('|', array_values($out));
        }

        $this->pool->wait();
        return ['run' => date('c'), 'jobs' => count($this->jobs), 'result' => $ja];
    }

    /**
     * 
     * @param string $name Referencia a ser localizada no arquivo config, caso necessario
     * @param string $description Descrição da operação
     * @param int $maxTimeExecution Tempo máximo de execução deste job
     * @param string $schedule '* * * * *' Expressão crontab para controle de execução
     * @param bool $isEnable Definição de esta habilitado ou não
     * @param Closure $closure Função para execução
     * @return void
     */
    public function add(
            string $name,
            string $description,
            int $maxTimeExecution,
            string $schedule,
            bool $isEnable,
            \Closure $closure
    ): JobbyRunner {
        $this->jobs[] = [$name, $description, $maxTimeExecution, $schedule, $isEnable, $closure];
        return $this;
    }

}
