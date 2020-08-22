<?php

namespace NsUtil;

class StatusLoader {

    private $startTime, $totalRegistros, $label, $size, $lastDone, $lastStatusBar;
    private $showQtde = false;

    public function __construct($totalRegistros, $label = '', $size = 45) {
        $this->startTime = time();
        $this->totalRegistros = (int) $totalRegistros;
        $this->size = (int) $size;
        $this->setLabel($label);
    }

    /**
     * Define se deve mostrar a quantidade total e a quantidade processada
     * @param boolean $show
     */
    public function setShowQtde(bool $show) {
        $this->showQtde = $show;
    }

    public function setLabel($label) {

        $s = (int) $this->size * 0.5;
        $label = str_pad($label, ($s + (int) $this->size));
        $label = mb_substr($label, 0, $s);
        $this->label = $label;
        if ($this->lastDone > 0) {
            $this->done($this->lastDone);
        }
    }

    public function done($done) {
        if ($done > $this->totalRegistros || $done === 0) {
            return;
        }

        $this->lastDone = $done;

        /*
          if (empty($this->startTime) || $done === 0) { // para iniciar
          $this->startTime = time();
          }
         * 
         */
        $now = time();

        $perc = (double) ($done / $this->totalRegistros);
        $tamanho = $this->size * 0.5;
        $bar = floor($perc * $tamanho);

        $status_bar = "\r" . $this->label . " [";
        $status_bar .= str_repeat("=", $bar);
        if ($bar < $tamanho) {
            $status_bar .= ">";
            $status_bar .= str_repeat(" ", $tamanho - $bar);
        } else {
            $status_bar .= "=";
        }

        $disp = number_format($perc * 100, 0);

        $status_bar .= "] $disp% done." . (($this->showQtde) ? ' ' . $done . '/' . $this->totalRegistros : '');

        $rate = ($now - $this->startTime) / $done;
        $left = $this->totalRegistros - $done;
        $eta = round($rate * $left, 2);

        $elapsed = $now - $this->startTime;

        $status_bar .= " Remaining: "
                . gmdate("H:i:s", (int) $eta)
                . ", Elapsed: "
                . gmdate("H:i:s", (int) $elapsed)
        ;
        echo "$status_bar  ";

        flush();

        // when done, send a newline
        if ($done == $this->totalRegistros) {
            // Salvar os dados do status bar concluido
            $this->lastStatusBar = $this->label . ': Elapsed ' . gmdate("H:i:s", (int) $elapsed);
            echo "\n";
        }
    }

    function getLastStatusBar() {
        return $this->lastStatusBar;
    }

}
