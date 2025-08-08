<?php

namespace Nasus\WebmanUtils\Command;

use Illuminate\Database\Connection;
use support\Db;
use Symfony\Component\Console\Command\Command;

class BaseCommand extends Command
{
    /**
     * 配置
     */
    const string ConfigPrefix = 'plugin.nasus.webman-utils';

    /**
     * 数据库连接
     * @var mixed
     */
    public string $connection;

    /**
     * @return Connection
     */
    protected function db()
    {
        return Db::connection(sprintf('%s.%s', self::ConfigPrefix, $this->connection));
    }
}