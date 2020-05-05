<?php
namespace App\Exception;

use App\Constants\ErrorCode;
use Hyperf\Server\Exception\ServerException;
use Throwable;

class HttpException extends ServerException
{
    public function __construct($code = 0, $message = "", Throwable $previous = null)
    {
        return parent::__construct($message, $code, $previous);
    }
}
