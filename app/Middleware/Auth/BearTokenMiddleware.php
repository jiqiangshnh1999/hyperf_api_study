<?php

declare(strict_types=1);

namespace App\Middleware\Auth;

use App\Exception\HttpException;
use App\Model\User;
use Firebase\JWT\JWT;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\RequestInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Hyperf\HttpServer\Contract\ResponseInterface as HttpResponse;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Hyperf\Redis\Redis;

class BearTokenMiddleware implements MiddlewareInterface
{
    //用户信息
    const USERINFOKEY = 'user_info_';
    //用户信息有效缓存时间
    const USERINFOTIMEOUT = 3600 * 4;
    //缓存token
    const USERTOKEN = 'user_token';
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var HttpResponse
     */
    protected $response;

    /**
     * @Inject()
     * @var Redis
     */
    public $redis;

    private $__authExcept = [
        'POST' => [
            '/users' => 1,
        ]
    ];

    /**
     * BearTokenMiddleware constructor.
     * @param ContainerInterface $container
     * @param RequestInterface $request
     * @param HttpResponse $response
     */
    public function __construct(ContainerInterface $container, RequestInterface $request, HttpResponse $response)
    {
        $this->container = $container;
        $this->request   = $request;
        $this->response  = $response;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $method = $this->request->getMethod();
        $pathInfo = $this->request->getPathInfo();

        if (isset($this->__authExcept[$method]) && isset($this->__authExcept[$method][$pathInfo]))
        {
            return $handler->handle($request);
        }

        $authorization = $this->request->header('Authorization', '');

        if (preg_match('/^Bearer\s+(.*?)$/', $authorization, $match))
        {
            $accessToken = $match[1];

            if ($this->redis->hExists(self::USERTOKEN, $accessToken))
            {
                $id = $this->redis->hGet(self::USERTOKEN, $accessToken);
            }else {
                $key = config('secret_salt');
                $method = config('secret_method');
                $accessInfo = JWT::decode($accessToken ,$key ,[$method]);
                $id = $accessInfo->jti->id;
                $this->redis->hSet(self::USERTOKEN, $accessToken, $id);
            }

            if (! $this->redis->exists(self::USERINFOKEY.$id)){
                //查询数据库user表
                $userInfo =  User::query()->where(['id' => $id])->first();
                //存在，则设置缓存
                if ($userInfo) {
                    $this->redis->set(self::USERINFOKEY.$id, time(), self::USERINFOTIMEOUT);
                }else {
                    //不存在，加入缓存，防止缓存穿透
                    $this->redis->set(self::USERINFOKEY.$id, null, 120);
                    throw new HttpException(401, '非法操作,用户身份不合法！');
                }
            }else {
                $userInfo = $this->redis->get(self::USERINFOKEY.$id);
            }

            //var_dump($this->redis->exec());

        }else {
            throw new HttpException(401, '非法操作,用户身份不合法！');
        }

        if (! $userInfo) {
            throw new HttpException(401, '非法操作,用户身份不合法！');
        }

        return $handler->handle($request);
    }
}