<?php

namespace NsUtil;

use Closure;
use Exception;
use NsUtil\Assync\Assync;
use NsUtil\CrontabCheck;
use NsUtil\Helper;
use NsUtil\Log;
use NsUtil\UniqueExecution;
use Spatie\Async\Pool;

class JobbyRunner
{

    private $logError;
    private $jobs = [];
    private $pool;
    private $printSuccessLog = false;

    public function __construct(?string $logfile = null)
    {
        $this->pool = new Assync();
        if (null !== $logfile) {
            $this->pool->setLogfile($logfile);
        }

        if (Helper::getSO() === 'windows') {
            die('ERROR: This class not running on windows');
        }
    }

    public function setAutoload(string $path): JobbyRunner
    {
        $this->pool->setAutoloader($path);
        return $this;
    }

    public function setConcurrency(?int $concurrency = null): JobbyRunner
    {
        $this->pool->setParallelProccess($concurrency);
        return $this;
    }

    public function setPrintSuccessLog(bool $printSuccessLog): JobbyRunner
    {
        $this->printSuccessLog = $printSuccessLog;
        return $this;
    }

    public function setLogfile(string $logfile): JobbyRunner
    {
        $this->pool->setLogfile($logfile);
        return $this;
    }


    public function run(string $verboseTitle = null): array
    {
        if (null !== $verboseTitle) {
            $this->pool->setShowLoader($verboseTitle);
        }

        // Executar os arquivos conforme regras
        $ja = [];

        $now = date('c');
        $printSuccessLog = $this->printSuccessLog;
        foreach ($this->jobs as $job) {
            list($name, $className, $functionName, $params, $maxTimeExecution, $schedule, $isEnable) = $job;
            $onlyOne = new UniqueExecution(md5(__FILE__ . $name));
            $out = [];
            $out[] = $name;
            $out[] = $schedule;
            $out[] = 'Enabled: ' . $isEnable ? 'true' : 'false';

            // Validação de execução
            switch (true) {
                case $onlyOne->isRunning($maxTimeExecution * 60):
                    $out[] = $onlyOne->getDefaultMessageIsRunning();
                    $className = '';
                    break;
                case (!$isEnable):
                    $out[] = 'Não habilitado';
                    $className = '';
                    break;
                case (!CrontabCheck::check($schedule)):
                    $out[] = 'Fora do prazo';
                    $className = '';
                    break;
                default:
                    $out[] = 'Execução agendada';
                    break;
            }

            // Programar a closure conforme chamado
            if (class_exists($className)) {
                $this->pool->addClassRunner($name, $className, $functionName, $params);
            }
            // fechar o processo unico de criação de nova closure
            $onlyOne->end();
            $ja[] = implode('|', array_values($out));
        }

        $this->pool->run();
        return ['run' => date('c'), 'jobs' => count($this->jobs), 'result' => $ja];
    }


    public function runClosure(string $verboseTitle = null): array
    {
        if (null !== $verboseTitle) {
            $this->pool->setShowLoader($verboseTitle);
        }

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
                $this->pool->addClosure($name, function () use ($name, $closure, $maxTimeExecution) {
                    $onlyOne = new UniqueExecution(md5(__FILE__ . $name));
                    if ($onlyOne->isRunning($maxTimeExecution * 60)) {
                        return $onlyOne->getDefaultMessageIsRunning();
                    } else {
                        $onlyOne->start();
                        $out = $closure();
                        $onlyOne->end();
                        return $out;
                    }
                });

                // fechar o processo unico de criação de nova closure
                $onlyOne->end();
            }
            $ja[] = implode('|', array_values($out));
        }

        $this->pool->run();
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
    public function addClosure(
        string $name,
        \Closure $closure,
        string $description = '',
        int $maxTimeExecution = 30,
        string $schedule = '* * * * *',
        bool $isEnable = true
    ): JobbyRunner {
        $this->jobs[] = [$name, $description, $maxTimeExecution, $schedule, $isEnable, $closure];
        return $this;
    }


    /**
     * Undocumented function
     *
     * @param string $name
     * @param string $className
     * @param string $functionName
     * @param array $params
     * @param integer $maxTimeExecution
     * @param string $schedule
     * @param boolean $isEnable
     * @return JobbyRunner
     */
    public function add(
        string $name,
        string $className,
        string $functionName,
        array $params = [],
        int $maxTimeExecution = 30,
        string $schedule = '* * * * *',
        bool $isEnable = true
    ): JobbyRunner {
        $this->jobs[] = [$name, $className, $functionName, $params, $maxTimeExecution, $schedule, $isEnable];
        return $this;
    }
}
