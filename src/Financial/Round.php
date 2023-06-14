<?php

namespace NsUtil\Financial;

use NsUtil\Financial\Contracts\Rounders;

class Round
{

    public static function handle(Rounders $rounder)
    {
        return $rounder->round();
    }
}
