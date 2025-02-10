<?php
namespace EasyScf;

class JWT {
    private $secret;

    public function __construct() {
        $config = require 'config.php';
        $this->secret = $config['jwt']['secret'] ?: $_ENV['JWT_SECRET'];
    }

    /**
     * 验证token
     * @param string $token
     * @return array
     */
    public function check($token) {
        $result = $this->verifyToken($token);
        if ($result['status']) {
            if ($result['data']->exp < time()) {
                return [false, 'token expired'];
            }
            return ['true', $result['data']];
        } else {
            return [false, $result['msg']];
        }
    }

    /**
     * 验证token
     * @param string $token
     * @return array
     */
    public function verifyToken($token) {
        $result = array(
            'status' => false,
            'msg'    => 'token error',
            'data'   => array(),
        );
        if (!$token) {
            return $result;
        } else {
            $data = \Firebase\JWT\JWT::decode($token, new \Firebase\JWT\Key($this->secret, 'HS256'));
            if (!$data) {
                $result['msg']    = 'verify error';
                return $result;
            }
            $result['status'] = true;
            $result['msg']    = 'success';
            $result['data']   = $data;
        }
        return $result;
    }

    /**
     * 创建token
     * @param array $data
     * @return string
     */
    public function create($data) {
        return \Firebase\JWT\JWT::encode($data, $this->secret, 'HS256');
    }
}
