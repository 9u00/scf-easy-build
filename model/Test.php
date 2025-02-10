<?php

namespace Model;

class Test extends \EasyScf\Model {
    public function __construct($db, $dbRead) {
        parent::__construct($db, $dbRead);
        // 表名
        $this->table = 'xxx';
        // 字段
        $this->fields = [];
        // 筛选字段
        $this->screenFields = [];
        // 文本字段
        $this->textFields = [];
        // 哈希ID
        $this->hashids = ['id'];
    }
}
