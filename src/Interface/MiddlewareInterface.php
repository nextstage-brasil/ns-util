<?php

namespace NsUtil\Interface;

use NsUtil\Api;

interface MiddlewareInterface
{
    public function __construct();
    public function handle(Api $api): bool;
}
