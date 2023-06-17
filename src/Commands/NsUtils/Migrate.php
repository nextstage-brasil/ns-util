<?php

namespace NsUtil\Commands\NsUtils;

use NsUtil\ConsoleTable;
use NsUtil\Helper;
use NsUtil\Template;
use NsUtil\Commands\Abstracts\Command;
use NsUtil\Databases\Migrations;
use NsUtil\DirectoryManipulation;
use NsUtil\Package;

use function NsUtil\dd;

class Migrate extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'migrate';

    public static array $conPreQuerys = [];

    public static string $pathOutput = '/tmp';


    /**
     * Handles the execution of the command.
     *
     * @param mixed $args The arguments passed to the command.
     * @return void
     */
    public function handle(array $args): void
    {

        $path = (getenv('COMMANDS_MIGRATIONS_PATH')
            ? getenv('COMMANDS_MIGRATIONS_PATH')
            : Helper::getPathApp() . '/_build/migrations');

        echo "\n";

        $ret = Migrations::builder(
            self::$pathOutput,
            Migrations::loadFromPath($path),
            self::$conPreQuerys
        );

        Helper::deleteDir(self::$pathOutput . '/_migrations');

        if ($ret['error'] !== false) {
            echo "\n" . ConsoleTable::setColor("Migrate ERROR!", 'red');
            dd($ret);
        }

        echo "\n"
            . ConsoleTable::setColor('Success!', 'green')
            . ' - '
            . ConsoleTable::setColor("Migrations ok!", 'blue')
            . "\n\n";
    }
}
