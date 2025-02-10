<?php

namespace Validate;

class Test extends \EasyScf\Validate {
 /**
     * 验证规则
     */
    protected $rule = [
        'id'              => 'min:1',
    ];

    /**
     * 字段描述
     */
    protected $field = [
        'id'              => 'ID',
    ];

    /**
     * 提示消息
     */
    protected $message = [
    ];

    /**
     * 验证场景
     */
    protected $scene = [
        'create' => [
            'id.',
        ],
    ];
}

