<?php

namespace DevGroup\FlexIntegration\errors;

use Exception;

class BaseException extends \Exception
{
    /** @var array Debug data */
    public $debug = [];

    public function __construct($message = "", $debug = [], $code = 0, \Exception $previous = null)
    {
        if ($message === '') {
            $message = $this->message;
        }
        parent::__construct($message, $code, $previous);
        $this->debug = $debug;
    }
}
