<?php

namespace NsUtil\Assync;

use Closure;
use Exception;
use NsUtil\Log;
use NsUtil\Helper;
use ReflectionClass;
use NsUtil\Eficiencia;
use NsUtil\StatusLoader;
use Laravel\SerializableClosure\SerializableClosure;

/**
 * Class that can execute a background job, and check if the
 * background job is still running
 * 
 * @package main
 */
class Assync
{

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
    private $testMode = false;

    // public function __construct(?int $parallelProcess = null, ?string $verboseTitle = null, ?string $autoloaderPath = null) {
    public function __construct(?string $verboseTitle = null)
    {
        if (Helper::getSO() === 'windows') {
            throw new Exception('NSUtil::Assync ERROR: Only linux systems can use this class');
        }
        $this->verbose = $verboseTitle;
        $this->status = new StatusLoader(count($this->list), 'NsPHPAssync');
        $db = debug_backtrace();
        $logfile = Log::getDefaultPathNSUtil() . DIRECTORY_SEPARATOR . isset($db[1]['function']) ? $db[1]['function'] : '';

        $this->setParallelProccess();
        $this->setAutoloader();
        $this->setLogfile($logfile);
        if ($verboseTitle) {
            $this->setShowLoader($verboseTitle);
        }
    }



    /**
     * 
     * @param string $path Comando a ser executado, ex.: php /local/do/arquivo.php
     * @param string $recurso Chave do comando a ser localizado no arquivo.php
     * @param array $params parametros a ser enviado para o executor
     */
    public function addByParams(string $path, string $recurso, array $params, string $className = null): Assync
    {
        $param['className'] = $className;
        $cmd = "{$path} {$recurso} " . base64_encode(json_encode($params));
        $this->add($cmd);
        return $this;
    }

    /**
     * Adiciona um processo a lista de execução
     *
     * @param string $cmd
     * @param string $outputfile
     * @return Assync
     */
    public function add(string $cmd, string $outputfile = '/dev/null'): Assync
    {
        $pidfile = '/tmp/' . hash('sha1', (string)$cmd);
        $this->list[] = ['command' => sprintf("%s > %s 2>&1 & echo $! > %s", $cmd, $outputfile, $pidfile), 'pidfile' => $pidfile, 'cmd' => $cmd];
        if ($this->verbose !== false) {
            $this->status = new StatusLoader(count($this->list), (string) $this->verbose);
            $this->status->setShowQtde(true);
        }
        return $this;
    }

    /**
     * Seta o autoloader automaticamente, ou definido manualmente
     *
     * @param string|null $autoloaderPath
     * @return self
     */
    public function setAutoloader(?string $autoloaderPath = null): self
    {
        $options = [$autoloaderPath];
        if (!$autoloaderPath) {
            $options = [
                Helper::getPathApp() . '/cron/autoload.php',
                Helper::getPathApp() . '/run/autoload.php',
                Helper::getPathApp() . '/vendor/autoload.php',
            ];
        }

        foreach ($options as $filename) {
            if (file_exists($filename)) {
                $this->autoloaderPath = $filename;
                return $this;
            }
        }

        throw new Exception("NSUtil Assync: autoload not find");
    }

    /**
     * Setter to logfile
     *
     * @param string $file
     * @return self
     */
    public function setLogfile(string $file): self
    {
        $this->logfile = $file;
        return $this;
    }

    public function setParallelProccess(?int $parallelProcess = null, $useAllProcessors = false)
    {
        if (null === $parallelProcess) {

            // Deixar um nucleo livre para demais tarefas
            $processors = 1;
            if (is_file('/proc/cpuinfo')) {
                $cpuinfo = file_get_contents('/proc/cpuinfo');
                preg_match_all('/^processor/m', $cpuinfo, $matches);
                $processors = count($matches[0]);
            }
            $parallelProcess = $useAllProcessors
                ? $processors
                : ($processors >= 2 ? ($processors - 1) : 1);
        }
        $this->limit = $parallelProcess;
        return $this;
    }

    public function setShowLoader(string $name)
    {
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
    public function addClosure(string $name, Closure $fn)
    {
        if (!$this->autoloaderPath) {
            throw new Exception("NSUtil Assync: autoload is not defined to closure");
        }
        $cmd = implode(' ', [
            PHP_BINARY,
            __DIR__ . '/ClosureRuntime.php',
            $this->autoloaderPath,
            self::encodeTask($fn),
            $this->logfile,
            $name
        ]);

        if ($this->testMode) {
            echo $cmd;
            die();
        }


        $this->add($cmd);

        return $this;
    }

    /**
     * Adiciona uma execução chamando uma classe da aplicação
     *
     * @param string $name
     * @param ReflectionClass $class
     * @param string $function
     * @param array $params
     * @return void
     */
    public function addClassRunner(string $name, string $className, string $function, array $params = [])
    {
        if (!$this->autoloaderPath) {
            throw new Exception("NSUtil Assync: autoload is not defined");
        }

        if (!class_exists($className)) {
            throw new Exception("NSUtil Assync: class '$className' not found");
        }
        if (!method_exists($className, $function)) {
            throw new Exception("NSUtil Assync: function '$function' not found on class '$className'");
        }
        $params = array_merge($params, [
            '__CLASS__' => $className,
            '__FUNCTION__' => $function
        ]);
        $cmd = implode(' ', [
            PHP_BINARY,
            __DIR__ . '/ClassRuntime.php',
            $this->autoloaderPath,
            base64_encode(json_encode($params)),
            $this->logfile,
            $name
        ]);
        $this->add($cmd);

        return $this;
    }

    public static function encodeTask($task): string
    {
        if ($task instanceof Closure) {
            $task = new SerializableClosure($task);
        }
        return base64_encode(serialize($task));
    }

    public static function decodeTask(string $task)
    {
        return unserialize(base64_decode($task));
    }


    /**
     * Executa os processos adicionados, limitando a N processos por vez, conforme configuração
     */
    public function run(?\Closure $onRunning = null)
    {
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
            if (is_callable($onRunning)) {
                $percentExecution = round($this->done / count($this->list) * 100, 0, PHP_ROUND_HALF_UP);
                call_user_func($onRunning, $percentExecution);
            }
            $this->checkRunning();
            return $this->run($onRunning);
        } else {
            $this->verbosePrint();
            $this->list = [];
            $this->done = 0;
            $this->isStarted = null;
            return $this->eficiencia->end()->text;
        }
    }

    private function verbosePrint()
    {
        if ($this->verbose !== null) {
            $this->status->done($this->done);
        }
    }

    /**
     * Verifica se a pilha tem processos concluidos e remove para inicio de outro processo
     */
    private function checkRunning()
    {
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
    private function isRunning($pid)
    {
        try {
            $result = shell_exec(sprintf("ps %d", $pid));
            if (count(preg_split("/\n/", $result)) > 2) {
                return true;
            }
        } catch (Exception $e) {
        }
        return false;
    }

    /**
     * Set the value of testMode
     *
     * @param boolean $testMode
     * @return self
     */
    public function setTestMode(bool $testMode): self
    {
        $this->testMode = $testMode;
        return $this;
    }
}
