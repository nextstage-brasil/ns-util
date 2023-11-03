<?php

namespace NsUtil\Exceptions;

use Exception;

class TooManyRequestException extends Exception
{
    public function __construct($message = "Limit exceeded")
    {
        parent::__construct($message, 429);
    }
}
