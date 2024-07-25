<?php

return [
    // 默认使用的数据库连接配置
    'default'         => 'mysql',

    // 自定义时间查询规则
    'time_query_rule' => [],

    // 自动写入时间戳字段
    // true为自动识别类型 false关闭
    // 字符串则明确指定时间字段类型 支持 int timestamp datetime date
    'auto_timestamp'  => false,

    // 时间字段取出后的默认时间格式
    'datetime_format' => 'Y-m-d H:i:s',

    // 数据库连接配置信息
    'connections'     => [
        'mysql' => [
            // 数据库类型
            'type'              => 'Wwws3\Kingbase\connector\KingBase',
            // 查询类型
            'builder'             => 'Wwws3\Kingbase\builder\KingBase',
            // 服务器地址
            'hostname'          => 'localhost',
            // 数据库名
            'database'          => 'test',
            // 用户名
            'username'          => 'SYSTEM',
            // 密码
            'password'          => 'KingBase',
            // 端口
            'hostport'          => '54321',
            // 数据库连接参数
            'params'            => [],
            // 数据库编码默认采用utf8
            'charset'           => '',
            // 数据库表前缀
            'prefix'            => '',
            // 开启自动主库读取
            'read_master'       => false,
            // 数据库部署方式:0 集中式(单一服务器),1 分布式(主从服务器)
            'deploy'            => 0,
            // 数据库读写是否分离 主从式有效
            'rw_separate'       => 0,
            // 读写分离后 主服务器数量
            'master_num'        => 1,
            // 指定从服务器序号
            'slave_no'          => '',
            // 是否严格检查字段是否存在
            'fields_strict'     => true,
            // 是否需要断线重连
            'break_reconnect'   => false,
            // 监听SQL
            'trigger_sql'       => true,
            // 开启字段缓存
            'fields_cache'      => false,
            // 字段缓存路径
            'schema_cache_path' => '',
        ],
    ],
];
