<?php
namespace EasyScf;

class Scf
{
    public $event;
    public $context;
    public $controller;
    public $model;
    public $validate;
    public $headers;
    public $params;
    public $body;
    public function __construct($event, $context)
    {
        $this->event = $event;
        $this->context = $context;
        $this->headers = $event->headers;
    }

    public function run()
    {
        $route = new Route($this->event, $this->context->function_name);
        list ($item, $controller, $function, $params, $body) = $route->init();
        if (!$item) {
            return [
                'code' => 404,
                'info' => '资源不存在'
            ];
        }

        //数据库链接
        list($dbRead, $db, $redis) = (new \EasyScf\Db)->init(6);

        $cName = '\Controller\\' . $controller;
        $c = new $cName($db, $dbRead, $redis);

        //禁用函数
        if (in_array($function, $c->disabledFun) || $c->disabledFun == '*') {
            return [
                'code' => 404,
                'info' => '资源不存在或禁用'
            ];
        }

        //鉴权
        $auth = new Auth($db, $dbRead, $redis);
        if (!$auth->authCheck($this->headers, $c->needLoginFun, $function)) {
            return $auth->getResponse();
        }

        if ($auth->uid && $this->event->httpMethod != 'GET' && $redis) {
            //处理同一用户并发写入
            $key = 'mbd_user_' . $controller . '_' . $function . '_' . $auth->uid;
            $item = $redis->setNx($key, 1);
            if (!$item) {
                return $c->error('请勿重复提交', 429);
            }
            $redis->expire($key, 30);
        }

        $c->setUid($auth->uid);
        $c->setUser($auth->user);
        $c->setHeaders($this->headers);
        $c->setIp($this->event->requestContext->sourceIp);

        $result = $c->$function($params, $body);
        if ($auth->uid && $this->event->httpMethod != 'GET' && $redis) {
            $redis->del($key);
        }

        return $result;
    }
}
