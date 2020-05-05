<?php
namespace App\Exception\Handler;

use Hyperf\ExceptionHandler\ExceptionHandler;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Psr\Http\Message\ResponseInterface;
use App\Exception\HttpException;
use Throwable;

class HttpExceptionHandler extends  ExceptionHandler
{
    public function handle(Throwable $throwable, ResponseInterface $response)
    {
        // 判断被捕获到的异常是希望被捕获的异常
        if ($throwable instanceof HttpException) {
            // 格式化输出
            $httpCode = $throwable->getCode();

            $data = json_encode(
                [
                    'success' => false,
                    'code' => $httpCode,
                    'data' => NULL,
                    'err_msg' => $throwable->getMessage()
                ], JSON_UNESCAPED_UNICODE);

            // 阻止异常冒泡
            $this->stopPropagation();
            return $response->withStatus($httpCode)->withBody(new SwooleStream($data))->withHeader('Content-Type', 'text/json');
        }

        // 交给下一个异常处理器
        return $response;

        // 或者不做处理直接屏蔽异常
    }

    /**
     * 判断该异常处理器是否要对该异常进行处理
     */
    public function isValid(Throwable $throwable): bool
    {
        return true;
    }
}
