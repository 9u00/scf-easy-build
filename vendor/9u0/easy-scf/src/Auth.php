<?php
namespace EasyScf;

class Auth extends Controller
{
    public $config;
    public function __construct($db, $dbRead, $redis = null)
    {
        parent::__construct($db, $dbRead, $redis);
        $this->config = require 'config.php';
    }

    /**
     * 鉴权
     */
    public function authCheck($headers, $needLoginFun, $function)
    {
        $cookie = $headers->cookie;
        $cookie = $this->str2arry($cookie);
        $c_h = json_decode(json_encode($headers),true);
        $mbd_t = $c_h['authorization'];
        $token = $mbd_t ?: $cookie['authorization'];

        if ($token) {
            try {
                list($result, $data) = (new \EasyScf\JWT())->check($token);
            } catch (\Exception $e) {
                $this->response = $this->error('鉴权失败', 401);
                return false;
            }
        }

        if ($result) {
            $this->uid  = $data->user_id;
            $userModelStr = '\Model\\' . $this->config['userModel'];
            $authInfoStr = $this->config['authInfo'];
            $usersModel = new $userModelStr($this->db, $this->dbRead);
            $this->user = $usersModel->$authInfoStr($this->uid);
            if (!$this->user && (in_array($function, $needLoginFun) || $needLoginFun == '*')) {
                $this->response = $this->error('用户不存在', 401);
                return false;
            }
            $this->user['token'] = $token;
            return true;
        } else if (in_array($function, $needLoginFun) || $needLoginFun == '*') {
            $this->response = $this->error($data, 401);
            return false;
        }

        return true;
    }

    public function str2arry($str) {
        $data = explode(';',$str);
        foreach ($data as $item) {
            $expl = explode('=',$item);
            $expl[0] = str_replace(' ','', $expl[0]);
            $result[$expl[0]] = $expl[1];
        }
        return $result;
    }
}