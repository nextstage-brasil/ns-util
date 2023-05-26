<?php

namespace NsUtil\Financial\Contracts;

interface Rounders
{
    public function __construct(float $value, int $precision = 2, array $configs = []);

    public function round(): float;

    public function _getRoundByRules(): int;
}
