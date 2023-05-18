<?php

namespace NsUtil\Commands\Contracts;

interface CommandInterface
{
    public function handle(array $params): void;
}
