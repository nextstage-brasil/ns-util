<?php

namespace NsUtil\Commands\NsUtils;

use NsUtil\Commands\Abstracts\Command;
use NsUtil\ConsoleTable;
use NsUtil\Services\RateLimiter;

use function NsUtil\dd;

class RateLimiterClear extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'rate:clear';

    /**
     * Handles the execution of the command.
     *
     * @param mixed $args The arguments passed to the command.
     * @return void
     */
    public function handle(array $args): void
    {

        $appkey = $args[0] ?? null;

        if (null !== $appkey) {
            RateLimiter::clear($appkey);
            echo ConsoleTable::setColor('Success!', 'green') . "\n";
        } else {
            echo ConsoleTable::setColor('Error! Required param 1: appkey or IP to clear', 'red') . "\n";
        }
    }
}
