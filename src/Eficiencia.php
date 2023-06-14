<?php

namespace NsUtil;

class Eficiencia {

    private $start;

    public function __construct() {
        $this->start = time();
    }

    public function end() {
        $elapsed_time = round(time() - $this->start, 2);
        $time = gmdate("H:i:s", (int) $elapsed_time);
        $memory = round(((memory_get_peak_usage(true) / 1024) / 1024), 2) . 'Mb';
        return (object) [
                    'text' => "Elapsed time: $time. Memory peak: $memory",
                    'time' => $time,
                    'memory' => $memory
        ];
    }

}
