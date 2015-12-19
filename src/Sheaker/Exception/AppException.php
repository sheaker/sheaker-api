<?php

namespace Sheaker\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class AppException extends HttpException
{
    public function __construct($statusCode, $message, $errorCode)
    {
        parent::__construct($statusCode, $message, null, [], $errorCode);
    }
}
