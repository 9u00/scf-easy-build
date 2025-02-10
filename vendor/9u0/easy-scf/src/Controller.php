<?php
namespace EasyScf;
use Hashids\Hashids;

class Controller
{
    public $response;
    public $uid                 = 0;
    public $user                = [];

    public $needLoginFun        = [];
    public $needKeyFun          = [];
    public $disabledFun         = [];
    public $notNeedDbFun        = ['getList', 'getDetail'];
    public $notNeedReadDbFun    = [];

    public $model;
    public $db;
    public $dbRead;
    public $setting;
    public $redis;
    public $ip;
    public $headers;
    public $hashidsModel;
    //构造函数
    public function __construct($db, $dbRead, $redis = null) {
        $this->db       = $db;
        $this->dbRead   = $dbRead;
        $this->redis    = $redis;
        $config         = require 'config.php';
        $this->hashidsModel = new Hashids($config['hashId']['salt'], $config['hashId']['length']);
    }

    //获取数据
    public function getResponse() {
        return $this->response;
    }

    /**
     * 获取列表数据
     * @param $params
     * @param $body
     * @return array|void
     */
    public function getList($params, $body) {
        $page     = $params['page'] ?: 1;
        $pageSize = $params['page_size'] ?: 999;
        $limit    = $pageSize * ($page - 1);
        $order    = $params['order'] ?: 'id';
        $sort     = $params['sort'] ?: 'DESC';
        unset($params['page'], $params['page_size']);

        $map = $oMap = $this->getMap($params);
        $map['LIMIT'] = [$limit, $pageSize];
        $map['ORDER'] = [$order => $sort];

        $list = $this->model->selectD($map);
        $count = $this->model->countD($oMap);
        $data = [
            'data'      => $list ?: [],
            'count'     => $count ?: 0,
            'next'      => $count > $pageSize * $page,
            'previous'  => $page > 1,
        ];
        $data['data'] = $this->datasEncodeHash($data['data']);
        return $this->success($data);
    }

    /**
     * 获取单条数据
     * @param $params
     * @param $body
     * @return array|void
     */
    public function getDetail($params, $body) {
        $map = $this->getMap($params);
        $data = $this->model->getD($map);
        if (!$data) {
            return $this->error('数据不存在');
        }
        $data = $this->dataEncodeHash($data);
        return $this->success(['data' => $data]);
    }

    /**
     * 创建数据
     * @param $params
     * @param $body
     * @return array|void
     */
    public function create($params, $body) {
        $body = $this->dataDecodeHash($body);
        foreach ($body as &$v) {
            if (is_array($v)) {
                $v = json_encode($v);
            }
        }
        $id = $this->model->insertD($body);
        if (!$id) {
            return $this->error('创建失败', 500);
        }
        if (in_array($this->model->id, $this->model->hashids)) {// 使用hashId
            $id = $this->encodeHash($id);
        }
        return $this->success(['data' => ['id' => $id] ], '创建成功', 201);
    }

    /**
     * 修改数据
     * @param $params
     * @param $body
     *
     * @return array|void
     */
    public function edit($params, $body) {
        $params = $this->dataDecodeHash($params);
        $body = $this->dataDecodeHash($body);
        $id = $params['id'];
        foreach ($body as &$v) {
            if (is_array($v)) {
                $v = json_encode($v);
            }
        }
        $result = $this->model->updateD($body, ['id' => $id]);
        if (!$result) {
            return $this->error('编辑失败', 500);
        }
        return $this->success();
    }

    /**
     * 删除数据
     * @param $params
     * @param $body
     *
     * @return array|void
     */
    public function delete($params, $body) {
        $params = $this->dataDecodeHash($params);
        $result = $this->model->deleteD(['id' => $params['id']]);

        if (!$result) {
            return $this->error('删除失败', 500);
        }
        return $this->success('删除成功', 204);
    }

    //成功返回
    public function success($response = [], $info = 'success', $code = 200) {
        if (!is_array($response)) {
            $info = $response;
            $response = [];
        }
        $responseD = [
            'code'  => $code,
            'info'  => $info,
        ];
        $response['data'] || $response['data'] = null;
        $responseD += $response;
        $this->response = $responseD;
        return $this->response;
    }

    //失败返回
    public function error($info = '', $code = 400, $data = null) {
        if (is_array($info)) {
            $data   = $info;
            $info   = 'fail';
        }
//        $data || $data = (Object) [];
        $this->response = [
            'code'   => $code,
            'info'   => $info,
            'data'   => $data,
        ];
        return $this->response;
    }

    public function setUid($uid) {
        $this->uid = $uid;
    }

    public function setUser($user) {
        $this->user = $user;
    }

    public function getMap($request) {
        $map = [];
        if ($this->model->screenFields) {
            foreach ($request as $k => $v) {
                $item = strstr($k, '[', true) ?: $k;
                $item && in_array($item, $this->model->screenFields) && $map[$k] = $v;
                if ($this->model->hashids && in_array($item, $this->model->hashids)) {
                    $map[$k] = $this->decodeHash($v);
                }
            }
        }
        $this->model->deleteField && $map[$this->model->deleteField] = null;
        return $map;
    }

    public function setSetting($setting) {
        $this->setting = $setting;
    }

    public function setIp($ip) {
        $this->ip = $ip;
    }

    public function setHeaders($headers) {
        $this->headers = $headers;
    }

    public function encodeHash($id) {
        return $this->hashidsModel->encode($id);
    }

    public function decodeHash($hash) {
        return $this->hashidsModel->decode($hash)[0];
    }

    public function dataEncodeHash($data, $hashids = []) {
        if (!$data) {
            return $data;
        }
        if (!$hashids) {
            $hashids = $this->model->hashids;
        }
        if (!$hashids) {
            return $data;
        }
        foreach ($data as $k => &$v) {
            if (in_array($k, $hashids)) {
                $v = $this->encodeHash($v);
            }
        }
        return $data;
    }

    public function datasEncodeHash($datas, $hashids = []) {
        if (!$datas) {
            return $datas;
        }
        foreach ($datas as &$data) {
            $data = $this->dataEncodeHash($data, $hashids);
        }
        return $datas;
    }

    public function dataDecodeHash($data, $hashids = []) {
        if (!$data) {
            return $data;
        }
        if (!$hashids) {
            $hashids = $this->model->hashids;
        }
        if (!$hashids) {
            return $data;
        }
        foreach ($data as $k => &$v) {
            if (in_array($k, $hashids)) {
                $v = $this->decodeHash($v);
            }
        }
        return $data;
    }

    public function datasDecodeHash($datas, $hashids = []) {
        if (!$datas) {
            return $datas;
        }
        foreach ($datas as &$data) {
            $data = $this->dataDecodeHash($data, $hashids);
        }
        return $datas;
    }
}
