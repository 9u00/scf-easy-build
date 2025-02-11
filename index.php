<?php
date_default_timezone_set('Asia/Shanghai');
header('Content-Type:text/html;charset=utf-8');
require_once __DIR__ . '/vendor/autoload.php';

use EasyScf\Scf;

function main_handler($event, $context) {
    $scf = new Scf($event, $context);
    return $scf->run();
}
