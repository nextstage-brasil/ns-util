<?php

namespace NsUtil\Assync;

use Closure;
use Exception;
use NsUtil\Eficiencia;
use NsUtil\Helper;
use NsUtil\StatusLoader;
use Laravel\SerializableClosure\SerializableClosure;

/**
 * Class that can execute a background job, and check if the
 * background job is still running
 * 
 * @package main
 */
class Assync {

    private $pid = 0;
    private $limit = 3;
    private $list = [];
    private $emAndamento = [];
    private $verbose;
    private $status;
    private $done = 0;
    private $eficiencia;
    private $autoloaderPath;
    private $logfile;
    private $isStarted;

    public function __construct(?int $parallelProcess = null, ?string $verboseTitle = null, ?string $autoloaderPath = null) {
        // Caso não informe, orei pegar o numero de nucleos disponiveis
        if (null === $parallelProcess) {
            $parallelProcess = 3;
            if (is_file('/proc/cpuinfo')) {
                $cpuinfo = file_get_contents('/proc/cpuinfo');
                preg_match_all('/^processor/m', $cpuinfo, $matches);
                $parallelProcess = count($matches[0]);
            }
        }
        $this->limit = $parallelProcess;
        $this->verbose = $verboseTitle;
        $this->setAutoloader($autoloaderPath);

        if (Helper::getSO() === 'windows') {
            throw new Exception('NSUtil::Assync ERROR: Only linux systems can use this class!');
        }
        $this->status = new StatusLoader(count($this->list), 'NsPHPAssync');
    }

    /**
     * 
     * @param string $path Comando a ser executado, ex.: php /local/do/arquivo.php
     * @param string $recurso Chave do comando a ser localizado no arquivo.php
     * @param array $params parametros a ser enviado para o executor
     */
    public function addByParams(string $path, string $recurso, array $params, string $className = null): Assync {
        $param['className'] = $className;
        $cmd = "${path} ${recurso} " . base64_encode(json_encode($params));
        $this->add($cmd);
        return $this;
    }

    /**
     * Adiciona um processo a lista de execução
     * @param type $cmd
     * @param type $outputfile
     * @return $this
     */
    public function add($cmd, $outputfile = '/dev/null'): Assync {
        $pidfile = '/tmp/' . hash('sha1', (string)$cmd);
        $this->list[] = ['command' => sprintf("%s > %s 2>&1 & echo $! > %s", $cmd, $outputfile, $pidfile), 'pidfile' => $pidfile, 'cmd' => $cmd];
        if ($this->verbose !== false) {
            $this->status = new StatusLoader(count($this->list), $this->verbose);
            $this->status->setShowQtde(true);
        }
        return $this;
    }

    public function setAutoloader(?string $autoloaderPath = null) {
        if (!$autoloaderPath) {
            $autoloaderPath = Helper::fileSearchRecursive('autoload.php', realpath(__DIR__ . '/../../'));
        }
        if (!file_exists($autoloaderPath)) {
            throw new Exception("NSUtil Assync: autoload on '$autoloaderPath' is not find");
        };

        $this->autoloaderPath = $autoloaderPath;
        return $this;
    }

    public function setLogfile(string $file) {
        $this->logfile = $file;
        return $this;
    }

    public function setParallelProccess(?int $parallelProcess = null) {
        if (null === $parallelProcess) {
            $parallelProcess = 3;
            if (is_file('/proc/cpuinfo')) {
                $cpuinfo = file_get_contents('/proc/cpuinfo');
                preg_match_all('/^processor/m', $cpuinfo, $matches);
                $parallelProcess = count($matches[0]);
            }
        }
        $this->limit = $parallelProcess;
        return $this;
    }

    public function setShowLoader(string $name) {
        $this->verbose = $name;
        return $this;
    }




    /**
     * Adiciona uma closure para execução em paralelo
     *
     * @param string $name Referência para registro em logs
     * @param Closure $fn Closure a ser executada
     * @return void
     */
    public function addClosure(string $name, Closure $fn) {
        if (!$this->autoloaderPath) {
            throw new Exception("NSUtil Assync: autoload is not defined to closure");
        }
        $cmd = implode(' ', [
            PHP_BINARY,
            __DIR__ . '/Runtime.php',
            $this->autoloaderPath,
            self::encodeTask($fn),
            $this->logfile,
            $name
        ]);
        $this->add($cmd);

        return $this;
    }

    public static function encodeTask($task): string {
        if ($task instanceof Closure) {
            $task = new SerializableClosure($task);
        }
        return base64_encode(serialize($task));
    }

    public static function decodeTask(string $task) {
        return unserialize(base64_decode($task));
    }


    /**
     * Executa os processos adicionados, limitando a N processos por vez, conforme configuração
     */
    public function run() {
        if (null === $this->isStarted) {
            $this->isStarted = true;
        }
        $this->checkRunning();
        if (!$this->eficiencia) {
            $this->eficiencia = new Eficiencia();
        }
        foreach ($this->list as $key => $item) {
            if (!isset($item['pid']) && count($this->emAndamento) < $this->limit) {
                $res = exec($item['command']);
                if (!$res) {
                    $pid = trim(file_get_contents($item['pidfile']));
                    $this->list[$key]['pid'] = $pid;
                    $this->emAndamento[$pid] = $this->list[$key];
                    unlink($item['pidfile']);
                } else {
                    die('error');
                }
            }
        }
        if (count($this->emAndamento) > 0) {
            $this->verbosePrint();
            // sleep(1);
            $this->checkRunning();
            return $this->run(); // looping até concluir
        } else {
            $this->verbosePrint();
            $this->list = [];
            $this->done = 0;
            $this->isStarted = null;
            return $this->eficiencia->end()->text;
        }
    }

    private function verbosePrint() {
        if ($this->verbose !== null) {
            $this->status->done($this->done);
        }
    }

    /**
     * Verifica se a pilha tem processos concluidos e remove para inicio de outro processo
     */
    private function checkRunning() {
        $out = "\n Processos em andamento: ";
        foreach ($this->emAndamento as $key => $val) {
            $out .= '[' . $this->emAndamento[$key]['pid'] . '] ';
            if (!$this->isRunning($val['pid'])) {
                $this->done++;
                unset($this->emAndamento[$key]);
            }
        }
        return true;
    }

    /**
     * Verifica o status do processo pelo PID
     * 
     * @param int $pid the process id to check for
     * @return boolean $res true if running or else false 
     */
    private function isRunning($pid) {
        try {
            $result = shell_exec(sprintf("ps %d", $pid));
            if (count(preg_split("/\n/", $result)) > 2) {
                return true;
            }
        } catch (Exception $e) {
        }
        return false;
    }
}
