<?php

namespace Nasus\WebmanUtils\Command;

use support\Db;
use Illuminate\Database\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;


abstract class BaseCommand extends Command
{
    /**
     * 配置
     */
    const string ConfigPrefix = 'plugin.nasus.webman-utils';

    /**
     * @return string
     */
    protected string $connection;

    /**
     * @var string
     */
    protected string $controllerNamespace;

    /**
     * @var string
     */
    protected string $modelNamespace;

    /**
     * @var string
     */
    protected string $requestNamespace;

    /**
     * public parameters
     *
     * @return void
     */
    protected function configure()
    {
        $defultConnection = config(sprintf('%s.database.default', self::ConfigPrefix));
        $this->addOption('connection', 'c', InputOption::VALUE_OPTIONAL, 'database connection', $defultConnection);
    }

    /**
     * @return Connection
     */
    protected function db()
    {
        return Db::connection(sprintf('%s.%s', self::ConfigPrefix, $this->connection));
    }

    /**
     * @param InputInterface $input
     * @return void
     */
    protected function initConf(InputInterface $input): void
    {
        $this->connection = $input->getOption('connection');

        $conf = config(sprintf('%s.database.connections.%s', self::ConfigPrefix, $this->connection));

        $this->controllerNamespace = $conf['controller'];
        $this->modelNamespace = $conf['model'];
        $this->requestNamespace = $conf['request'];
    }

    /**
     * @param $path
     * @return string
     */
    protected function module($path): string
    {
        $mp = dirname($path);
        return $mp === '.' ? '' : strtolower($mp);
    }
}