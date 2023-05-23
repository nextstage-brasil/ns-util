<?php

namespace NsUtil\Commands\NsUtils;

use NsUtil\ConsoleTable;
use NsUtil\Helper;
use NsUtil\Template;
use NsUtil\Commands\Abstracts\Command;
use NsUtil\DirectoryManipulation;

class MakeMigration extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'make:migration';

    /**
     * Handles the execution of the command.
     *
     * @param mixed $args The arguments passed to the command.
     * @return void
     */
    public function handle(array $args): void
    {
        if (null === $args[0]) {
            throw new \Exception('Name of new command was not informed. Use: php nsutil make:migration MigrationName');
        }
        $className = ucwords(Helper::name2CamelCase($args[0]));
        $path = (getenv('COMMANDS_MIGRATIONS_PATH')
            ? getenv('COMMANDS_MIGRATIONS_PATH')
            : Helper::getPathApp() . '/_build/install/migrations');
        $lastfiletime = DirectoryManipulation::getLastFileCreated($path) ?? 0;
        $filename = $path
            . DIRECTORY_SEPARATOR
            . "_{$lastfiletime}_"
            . mb_strtolower($className) . '.sql';

        if (!Helper::saveFile($filename, false, '')) {
            throw new \Exception('It is not possible to create the file ' . $filename);
        }

        chmod($filename, 0777);

        echo "\n"
            . ConsoleTable::setColor('Success!', 'green')
            . ' - '
            . ConsoleTable::setColor("File $filename was created!", 'blue')
            . "\n\n";
    }
}
