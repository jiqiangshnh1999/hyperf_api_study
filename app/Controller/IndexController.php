<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://doc.hyperf.io
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf-cloud/hyperf/blob/master/LICENSE
 */

namespace App\Controller;

use Firebase\JWT\JWT;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\PostMapping;
use Psr\Http\Message\ResponseInterface;

/**
 * Class IndexController
 * @package App\Controller
 * @Controller(prefix="users")
 */
class IndexController extends AbstractController
{
    public function index()
    {
        $user = $this->request->input('user', 'Hyperf');
        $method = $this->request->getMethod();

        return [
            'method' => $method,
            'message' => "Hello {$user}.",
        ];
    }

    /**
     * @PostMapping(path="")
     */
    public function save() :ResponseInterface
    {
        $arr = [
            'iss' => 'www.baidu.com',
            'aud' => 'www.baidu.com',
            'iat' => time(),
            'nbf' => time(),
            'jti' => [
                'id' => 1,
                'phone' => '13388888888'
            ]
        ];

        $token = JWT::encode($arr, config('secret_salt'), config('secret_method'));

        $data = [
            'success' => true,
            'code' => 200,
            'data' => [
                'token' => $token
            ]
        ];

        return $this->response->json($data);
    }

    /**
     * @GetMapping(path="/time")
     */
    public function time() :ResponseInterface
    {
        $data = [
            'success' => true,
            'code' => 200,
            'data' => [
                'time' => time()
            ],
            'err_msg' => ''
        ];

        return $this->response->json($data);
    }
}
