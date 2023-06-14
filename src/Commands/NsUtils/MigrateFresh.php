<?php

namespace NsUtil\Commands\NsUtils;

use NsUtil\Config;
use NsUtil\Helper;
use NsUtil\ConsoleTable;
use NsUtil\ConnectionPostgreSQL;
use NsUtil\Databases\Migrations;
use NsUtil\Commands\Abstracts\Command;


class MigrateFresh extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'migrate:fresh';

    /**
     * Handles the execution of the command.
     *
     * @param mixed $args The arguments passed to the command.
     * @return void
     */
    public function handle(array $args): void
    {
        // Remover tudo
        $config = new Config(getenv());
        $con = new ConnectionPostgreSQL($config->get('DBHOST'), $config->get('DBUSER'), $config->get('DBPASS'), $config->get('DBPORT'), $config->get('DBNAME'));
        $con->begin_transaction();
        $con->executeQuery("DROP SCHEMA IF EXISTS _dbupdater CASCADE");
        $con->executeQuery("DROP SCHEMA public CASCADE");
        $con->executeQuery("CREATE SCHEMA public");

        $con->commit();

        (new Migrate())->handle([]);
    }
}
