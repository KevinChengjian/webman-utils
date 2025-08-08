<?php

return [
    'default' => 'cli',
    'connections' => [
        'cli' => [
            'model' => 'app\model',
            'request' => 'app\request',
            'controller' => 'app\controller',

            'driver' => 'mysql',
            'host' => getenv('CLI_DB_HOSE'),
            'port' => getenv('CLI_DB_PORT'),
            'database' => getenv('CLI_DB_NAME'),
            'username' => getenv('CLI_DB_USER'),
            'password' => getenv('CLI_DB_PWD'),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_general_ci',
            'prefix' => getenv('CLI_DB_PREFIX'),
            'strict' => true,
            'engine' => null,
            'options' => [
                PDO::ATTR_EMULATE_PREPARES => false, // Must be false for Swoole and Swow drivers.
            ],
            'pool' => [
                'max_connections' => 5,
                'min_connections' => 1,
                'wait_timeout' => 3,
                'idle_timeout' => 60,
                'heartbeat_interval' => 50,
            ],
        ],
    ],
];