<?php

namespace NsUtil\Assync;

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

    public function __construct(int $limit = 3, $verboseTitle = false) {
        $this->limit = $limit;
        if ($verboseTitle) {
            $this->verbose = $verboseTitle;
        }
        $this->status = new \NsUtil\StatusLoader(count($this->list), 'NsPHPAssync');
    }

    /**
     * Adiciona um processo a lista de execução
     * @param type $cmd
     * @param type $outputfile
     * @return $this
     */
    public function add($cmd, $outputfile = '/dev/null') {
        $pidfile = '/tmp/' . hash('sha1', $cmd);
        $this->list[] = ['command' => sprintf("%s > %s 2>&1 & echo $! > %s", $cmd, $outputfile, $pidfile), 'pidfile' => $pidfile, 'cmd' => $cmd];
        if ($this->verbose !== false) {
            $this->status = new \NsUtil\StatusLoader(count($this->list), $this->verbose);
        }
        return $this;
    }

    /**
     * Executa os processos adicionados, limitando a N processos por vez, conforme configuração
     */
    public function run() {
        $this->checkRunning();
        if (!$this->eficiencia) {
            $this->eficiencia = new \NsUtil\Eficiencia();
        }
        foreach ($this->list as $key => $item) {
            if (!$item['pid'] && count($this->emAndamento) < $this->limit) {
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
            sleep(1);
            $this->checkRunning();
            return $this->run(); // looping até concluir
        } else {
            $this->verbosePrint();
            $this->list = [];
            $this->done = 0;
            return $this->eficiencia->end()->text;
        }
    }

    private function verbosePrint() {
        if ($this->verbose !== false) {
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
