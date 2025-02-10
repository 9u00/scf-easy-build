<?php
namespace EasyScf;

Class Validate {

    public $msg = 'OK';

    //全部规则
    protected $rule    = [];
    //字段解释
    protected $field   = [];
    //自定义消息
    protected $message = [];
    //场景
    protected $scene   = [];
    //使用规则
    protected $useRule = [];
    //数据
    public    $data    = [];

    protected $msgArr  = [
        'require'       => '不能为空',
        'int'           => '必须为整数',
        'number'        => '必须为数字',
        'bool'          => '必须为布尔值',
        'date'          => '必须为日期格式',
        'mobile'        => '必须为手机号',
        'email'         => '必须为邮箱',
        'en'            => '必须为英文',
        'cn'            => '必须为中文',
        'gt'            => ['必须大于', ''],
        'egt'           => ['必须大于等于', ''],
        'lt'            => ['必须小于', ''],
        'elt'           => ['必须小于等于', ''],
        'eq'            => ['必须等于', ''],
        'min'           => ['最小为', '', '位'],
        'max'           => ['最大为', '', '位'],
        'between'       => ['必须在', '', '和', '', '之间'],
        'in'            => ['必须在', '', '之中'],
    ];

    public function __construct() {
        $this->useRule = $this->rule;
    }

    public function scene($key) {
        $this->useRule = [];
        $scene      = $this->scene[$key];
        $scene      = $this->requirePointCheck($scene);
        foreach ($this->rule as $k => $v) {
            in_array($k, $scene) && $this->useRule[$k] .= $v;
        }
        return $this;
    }

    /**
     * New
     * 新的必填规则，关键字 + '.';
     * @return void
     */
    public function requirePointCheck($scene) {
        $itemS = [];
        foreach ($scene as $item) {
            $itemK = strstr($item, '.', true);
            if ($itemK) {
                $this->useRule[$itemK] = 'require';
                $this->rule[$itemK] && $this->useRule[$itemK] .= '|';
            } else {
                $this->useRule[$item]   = '';
            }
            $itemS[] = $itemK ?: $item;
        }
        return $itemS;
    }

    public function check($data) {
        $this->data = $data;
        if (!$this->useRule) {
            return true;
        }
        foreach ($this->useRule as $k => $v) {
            $rules = explode('|', $v);
            foreach ($rules as $kk => $vv) {
                $ex     = [];
                $exArr  = explode(':', $vv);
                $type   = $vv;
                if (count($exArr) > 1) {
                    $ex   = explode(',', $exArr[1]);
                    $type = $exArr[0];
                }
                $result = $this->$type($k, $ex);
                if (!$result) {
                    return false;
                }
            }
        }
        return true;
    }

    private function require($field, $ex) {
        $empty = ['', "", NULL];
        if (!isset($this->data[$field]) || in_array($this->data[$field], $empty, true) ) {
            $item = in_array($this->data[$field], $empty, true);
            $this->setMsg($field, 'require', $ex);
            return false;
        }
        return true;
    }

    private function int($field, $ex) {

        if (!isset($this->data[$field])) {
            return true;
        }
        $value = $this->data[$field];
        if (!is_numeric($value) || floor($value) - $value != 0) {
            $this->setMsg($field, 'int', $ex);
            return false;
        }
        return true;
    }

    private function number($field, $ex) {
        if (!isset($this->data[$field])) {
            return true;
        }
        $value = $this->data[$field];
        if (!is_numeric($value)) {
            $this->setMsg($field, 'float', $ex);
            return false;
        }
        return true;
    }

    private function bool($field, $ex) {
        if (!isset($this->data[$field])) {
            return true;
        }
        $value = $this->data[$field];
        if (!is_bool($value)) {
            $this->setMsg($field, 'bool', $ex);
            return false;
        }
        return true;
    }

    private function date($field, $ex) {
        if (!isset($this->data[$field])) {
            return true;
        }
        $value = $this->data[$field];
        if (!strtotime($value)) {
            $this->setMsg($field, 'date', $ex);
            return false;
        }
        return true;
    }

    private function mobile($field, $ex) {
        if (!isset($this->data[$field])) {
            return true;
        }
        $value = $this->data[$field];
        if(!preg_match("/^1(3[0-9]|4[01456879]|5[0-35-9]|6[2567]|7[0-8]|8[0-9]|9[0-35-9])\d{8}$/", $value) ) {
            $this->setMsg($field, 'mobile', $ex);
            return false;
        }
        return true;
    }

    private function email($field, $ex) {
        if (!isset($this->data[$field])) {
            return true;
        }
        $value = $this->data[$field];
        if(!preg_match("/\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*/", $value) ) {
            $this->setMsg($field, 'email', $ex);
            return false;
        }
        return true;
    }

    private function gt($field, $ex) {
        if (!isset($this->data[$field])) {
            return true;
        }
        $value = $this->data[$field];
        if($value <= $ex[0]) {
            $this->setMsg($field, 'gt', $ex);
            return false;
        }
        return true;
    }

    private function egt($field, $ex) {
        if (!isset($this->data[$field])) {
            return true;
        }
        $value = $this->data[$field];
        if($value < $ex[0]) {
            $this->setMsg($field, 'egt', $ex);
            return false;
        }
        return true;
    }

    private function lt($field, $ex) {
        if (!isset($this->data[$field])) {
            return true;
        }
        $value = $this->data[$field];
        if($value >= $ex[0]) {
            $this->setMsg($field, 'lt', $ex);
            return false;
        }
        return true;
    }

    private function elt($field, $ex) {
        if (!isset($this->data[$field])) {
            return true;
        }
        $value = $this->data[$field];
        if($value > $ex[0]) {
            $this->setMsg($field, 'elt', $ex);
            return false;
        }
        return true;
    }

    private function eq($field, $ex) {
        if (!isset($this->data[$field])) {
            return true;
        }
        $value = $this->data[$field];
        if($value != $ex[0]) {
            $this->setMsg($field, 'eq', $ex);
            return false;
        }
        return true;
    }

    private function min($field, $ex) {
        if (!isset($this->data[$field])) {
            return true;
        }
        $value = $this->data[$field];
        $len = is_array($value) ? count($value) : mb_strlen($value);
        var_dump($value);
        var_dump($len);
        var_dump($ex);
        if ($len < $ex[0]) {
            $this->setMsg($field, 'min', $ex);
            return false;
        }
        return true;
    }

    private function max($field, $ex) {
        if (!isset($this->data[$field])) {
            return true;
        }
        $value = $this->data[$field];
        $len = is_array($value) ? count($value) : mb_strlen($value);
        if ($len > $ex[0]) {
            $this->setMsg($field, 'max', $ex);
            return false;
        }
        return true;
    }

    private function between($field, $ex) {
        if (!isset($this->data[$field])) {
            return true;
        }
        $value = $this->data[$field];
        if ($value < $ex[0] || $value > $ex[1]) {
            $this->setMsg($field, 'between', $ex);
            return false;
        }
        return true;
    }

    private function in($field, $ex) {
        if (!isset($this->data[$field])) {
            return true;
        }
        $value = $this->data[$field];
        if (!in_array($value, $ex)) {
            $this->setMsg($field, 'in', $ex);
            return false;
        }
        return true;
    }

    private function setMsg($field, $type, $ex) {
        if (isset($this->message["$field.$type"]) ) {
            $this->msg = $this->message["$field.$type"];
            return;
        }
        $name   = !empty($this->field[$field]) ? $this->field[$field] : $field;
        $msg    = $name;
        $msgArr = $this->msgArr[$type];
        if (!is_array($msgArr)) {
            $this->msg = $msg . $msgArr;
            return;
        }
        $i = 0;
        foreach ($msgArr as $k => $v) {
            if ($v) {
                $msg .= $v;
            } else {
                $msg .= $ex[$i];
                $i++;
            }
        }
        $this->msg = $msg;
        return;
    }
}