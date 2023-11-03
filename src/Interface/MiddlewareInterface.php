<?php

namespace NsUtil\Interface;

use NsUtil\Api;

interface MiddlewareInterface
{
    public function handle(Api $api): bool;

    public function check(): bool;
}
