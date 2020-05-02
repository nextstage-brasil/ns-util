<?php

namespace NsUtil;

class StatusLoader {

    private $startTime, $totalRegistros, $label, $size, $lastDone;
    private $showQtde = false;

    public function __construct($totalRegistros, $label = '', $size = 20) {
        $this->startTime = time();
        $this->totalRegistros = (int) $totalRegistros;
        $this->setLabel($label);
        $this->size = (int) $size;
    }

    /**
     * Define se deve mostrar a quantidade total e a quantidade processada
     * @param boolean $show
     */
    public function setShowQtde(bool $show) {
        $this->showQtde = $show;
    }

    function setLabel($label) {
        if (strlen($label) > 20) {
            $label = mb_substr($label, 0, 20);
        } else {
            $label = str_pad($label, 20);
        }
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

        $bar = floor($perc * $this->size);

        $status_bar = "\r" . $this->label . " [";
        $status_bar .= str_repeat("=", $bar);
        if ($bar < $this->size) {
            $status_bar .= ">";
            $status_bar .= str_repeat(" ", $this->size - $bar);
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
            echo "\n";
        }
    }

}
