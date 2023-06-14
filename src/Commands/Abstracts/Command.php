<?php

namespace NsUtil\Commands\Abstracts;


use NsUtil\Helper;
use NsUtil\ConsoleTable;
use NsUtil\Commands\Contracts\CommandInterface;
use NsUtil\Log;

abstract class Command implements CommandInterface
{

    public function __construct()
    {
    }


    private function logger($message)
    {
        if (getenv('COMMANDS_LOGFILE')) {
            Log::logTxt(getenv('COMMANDS_LOGFILE'), json_encode($message), true);
        }
    }
    private function __printResult($result, $info)
    {
        $this->logger("[$result] $info");

        echo PHP_EOL;

        echo Helper::compareString('success', $result)
            ? "✅ " . ConsoleTable::setColor($result, 'green')
            : "❌ " . ConsoleTable::setColor($result, 'red');

        echo ": ";

        echo ConsoleTable::setColor($info, 'blue');

        echo PHP_EOL;
        echo PHP_EOL;
    }

    public function success($info)
    {
        $this->__printResult('Success', $info);
    }

    public function error($info)
    {
        $this->__printResult('Error', $info);
    }
}
