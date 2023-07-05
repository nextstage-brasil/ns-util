<?php

namespace NsUtil\Connection;

use PDO;

interface ConnectionInterface
{
    public function open();
    public function getConn();
    public function close();
    public function begin_transaction();
    public function commit();
    public function rollback();
    public function autocommit($boolean);
    public function executeQuery($query, $params = null);
    public function next();
    public function execQueryAndReturn(string $query, bool $log = true, bool $keyCamelCaseFormat = true): array;
    public function execQueryAndReturnPrepared(string $query, ?array $params, bool $keyCamelCaseFormat = true): array;
    public function setNullAs($nullAs = '');
    public function insert($table, $array, $nomeCpoId, $onConflict = '');
    public function update($table, $array, $cpoWhere);
}
