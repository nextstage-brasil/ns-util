<?php

namespace NsUtil\Commands\NsUtils;

use NsUtil\Helper;
use NsUtil\Template;
use NsUtil\ConsoleTable;
use NsUtil\Commands\Abstracts\Command;

class MakeRouter extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'make:api-router';

    /**
     * Handles the execution of the command.
     *
     * @param mixed $args The arguments passed to the command.
     * @return void
     */
    public function handle(array $args): void
    {
        if (null === $args[0]) {
            throw new \Exception('Name of new router was not informed. Use: php nsutil make:router RouterName');
        }

        // create dir
        $pathToCommands = Helper::getPathApp() . '/src/Controllers/Api';
        Helper::mkdir($pathToCommands);

        $className = ucwords(Helper::name2CamelCase($args[0]));
        $filename = $pathToCommands . "/$className.php";
        $commandName = str_ireplace(['_router', 'router'], '', Helper::sanitize(Helper::reverteName2CamelCase($className)));
        $namespace = Helper::getPsr4Name() . '\Controllers\Api';
        $signature = str_ireplace(['_', 'command'], [':', ''], $commandName);
        $template = include __DIR__ . '/../Templates/router.php';
        $content = (new Template($template, [
            'namespace' => $namespace,
            'classname' => $className,
            'signature' => $signature
        ]))->render();

        if (file_exists($filename)) {
            throw new \Exception("Error: file $filename was exists");
        }

        if (!Helper::saveFile($filename, false, $content)) {
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
