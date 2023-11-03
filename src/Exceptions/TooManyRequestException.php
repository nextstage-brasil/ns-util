<?php

namespace NsUtil\Exceptions;

use Exception;

class TooManyRequestException extends Exception
{
    public function __construct($message = null, $code = 0, $previous = null)
    {
        if ($message === null || strlen($message) === 0) {
            $message = "Limit exceeded";
        }

        parent::__construct($message, 429, $previous);
    }
}
