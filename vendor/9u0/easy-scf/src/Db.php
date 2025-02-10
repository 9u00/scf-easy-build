<?php
namespace EasyScf;
use Medoo\Medoo;

class Db
{
    public $config;
    public function __construct()
    {
        $this->config = require 'config.php';
    }

    /**
     * 初始化数据库
     *
     * @param $item 1=读，2=写，3=redis，4=读+redis，5=写+redis，6=读+写+redis
     * @return void
     */
    public function init($item = 6) {
        switch ($item) {
            case 1:
                return $this->dbRead();
            case 2:
                return $this->db();
            case 3:
                return $this->redis();
            case 4:
                return [$this->dbRead(), $this->redis()];
            case 5:
                return [$this->db(), $this->redis()];
            case 6:
            default:
                return [$this->db(), $this->dbRead(), $this->redis()];
        }
    }

    public function db()
    {
        try {
            return new Medoo($this->config['db']['write']);
        } catch (\Exception $e) {
            error_log('数据库连接失败: ' . $e->getMessage());
            return null;
        }
    }

    public function dbRead()
    {
        try {
            return new Medoo($this->config['db']['read']);
        } catch (\Exception $e) {
            error_log('数据库连接失败: ' . $e->getMessage());
            return null;
        }
    }

    public function redis()
    {
        try {
            $redis = new \Redis();
            $redis->connect($this->config['db']['redis']['host'], $this->config['db']['redis']['port']);
            $redis->auth($this->config['db']['redis']['password']);
            return $redis;
        } catch (\Exception $e) {
            error_log('redis连接失败: ' . $e->getMessage());
            return null;
        }
    }
}