<?php

namespace EasyScf;

class Curl
{
    /**
     * GET请求
     */
    public function get($url, $data = null, $header = null) {
        return $this->curl($url, $data, $header, 'GET');
    }
    
    /**
     * POST请求
     */
    public function post($url, $data = null, $header = null) {
        return $this->curl($url, $data, $header, 'POST');
    }

    /**
     * PUT请求
     */
    public function put($url, $data = null, $header = null) {
        return $this->curl($url, $data, $header, 'PUT');
    }

    /**
     * DELETE请求
     */
    public function delete($url, $data = null, $header = null) {
        return $this->curl($url, $data, $header, 'DELETE');
    }

    /**
     * curl请求
     */
    public function curl($url, $data = null, $header = null, $method = 'POST') {
        $data && is_array($data) && $data = json_encode($data);
        $handle = curl_init();
        curl_setopt($handle, CURLOPT_URL, $url); // 要访问的地址
        curl_setopt($handle,CURLOPT_HEADER,1); // 是否显示返回的Header区域内容
        $header && curl_setopt($handle, CURLOPT_HTTPHEADER, $header); //设置请求头
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true); // 获取的信息以文件流的形式返回
        curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false); // 从证书中检查SSL加密算法是否存在
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false); // 对认证证书来源的检查

        switch($method) {
            case 'GET':
                break;
            case 'POST':
                curl_setopt($handle, CURLOPT_POST, true);
                curl_setopt($handle, CURLOPT_POSTFIELDS, $data); //设置请求体，提交数据包
                break;
            case 'PUT':
                curl_setopt($handle, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($handle, CURLOPT_POSTFIELDS, $data); //设置请求体，提交数据包
                break;
            case 'DELETE':
                curl_setopt($handle, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
        }

        $response = curl_exec($handle); // 执行操作
        $code = curl_getinfo($handle, CURLINFO_HTTP_CODE); // 获取返回的状态码
//        $data = curl_multi_getcontent($handle); // 获取返回的数据
        var_dump($response);
        var_dump($code);
        curl_close ($handle); // 关闭CURL会话
        return ['code' => $code, 'data' => $this->response2arr($response)];
    }

    function response2arr($response) {
        var_dump($response);
        $arr = explode("\r\n\r\n", $response);
        var_dump($arr);
        return json_decode(array_pop($arr), true);
    }
}