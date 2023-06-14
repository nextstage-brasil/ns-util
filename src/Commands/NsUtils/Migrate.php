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

    /**
     * Handles the execution of the command.
     *
     * @param mixed $args The arguments passed to the command.
     * @return void
     */
    public function handle(array $args): void
    {

        $path = Helper::getPathApp() . '/_build/migrations';

        Migrations::builder(
            '/tmp',
            Migrations::loadFromPath($path)
        );

        Migrations::run(
            '/tmp',
            [],
            true
        );


        echo "\n"
            . ConsoleTable::setColor('Success!', 'green')
            . ' - '
            . ConsoleTable::setColor("Migrations ok!", 'blue')
            . "\n\n";
    }
}
