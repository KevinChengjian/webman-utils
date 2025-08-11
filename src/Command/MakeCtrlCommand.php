<?php

namespace Nasus\WebmanUtils\Command;

use Nasus\WebmanUtils\Utils\Helper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('make:ctrl', 'Make Controller')]
class MakeCtrlCommand extends BaseCommand
{
    /**
     * @return void
     */
    protected function configure()
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'controller name');
        $this->addArgument('methods', InputArgument::IS_ARRAY, 'controller methods');
        $this->addOption('model', 'm', InputOption::VALUE_OPTIONAL, 'model name');
        $this->addOption('connection', 'c', InputOption::VALUE_OPTIONAL, 'database connection');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->connection = $input->getOption('connection') ?? config(sprintf('%s.database.default', self::ConfigPrefix));
        $ctrlName = Helper::SnakeToCamel($input->getArgument('name'));
        if (!str_ends_with($ctrlName, 'Controller')) {
            $ctrlName = $ctrlName . 'Controller';
        }

        $ctrlNamespace = config(sprintf('%s.database.connections.%s.controller', self::ConfigPrefix, $this->connection));
        $modelNamespace = config(sprintf('%s.database.connections.%s.model', self::ConfigPrefix, $this->connection));
        $requestNamespace = config(sprintf('%s.database.connections.%s.request', self::ConfigPrefix, $this->connection));

        $useNamespace = [
            'use Nasus\WebmanUtils\Annotation\RequestMapping;',
            'use Webman\Http\Response;',
        ];

        $addRequest = [];
        if (!empty($input->getOption('model'))) {
            $model = Helper::SnakeToCamel($input->getOption('model'));
            if (!file_exists(base_path($modelNamespace . DIRECTORY_SEPARATOR . $model . '.php'))) {
                $output->write(sprintf('<error>The %s\%s file does not exist!</error>', $modelNamespace, $model));
                return self::FAILURE;
            }

            $useNamespace[] = sprintf('use %s\\%s;', $modelNamespace, $model);
            $useNamespace[] = sprintf('use %s\\do\\%sDo;', $modelNamespace, $model);
        }

        $ctrlMethod = '';
        $onlyName = ucfirst(str_replace('Controller', '', $ctrlName));
        $funcPath = __DIR__ . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'func' . DIRECTORY_SEPARATOR;
        foreach ($input->getArgument('methods') as $method) {
            if ($method == 'list') {
                $useNamespace[] = 'use Nasus\WebmanUtils\Request\PagingRequest;';
                $ctrlMethod .= file_get_contents($funcPath . 'list.stub');
            } else if ($method == 'create') {
                $useNamespace[] = sprintf('use %s\\%sStoreRequest;', $requestNamespace, $onlyName);
                $ctrlMethod .= str_replace('{onlyName}', $onlyName, file_get_contents($funcPath . 'create.stub'));
                $addRequest[] = $onlyName . 'StoreRequest';
            } else if ($method == 'update') {
                $useNamespace[] = sprintf('use %s\\%sStoreRequest;', $requestNamespace, $onlyName);
                $useNamespace[] = 'use Nasus\WebmanUtils\Request\UpdateIdRequest;';
                $ctrlMethod .= str_replace('{onlyName}', $onlyName, file_get_contents($funcPath . 'update.stub'));
                $addRequest[] = $onlyName . 'StoreRequest';
            } else if ($method == 'delete') {
                $useNamespace[] = 'use Nasus\WebmanUtils\Request\DeleteIdRequest;';
                $ctrlMethod .= file_get_contents($funcPath . 'delete.stub');
            } else if ($method == 'detail') {
                $useNamespace[] = 'use Nasus\WebmanUtils\Request\DetailIdRequest;';
                $ctrlMethod .= file_get_contents($funcPath . 'detail.stub');
            } else {
                $mc = file_get_contents($funcPath . 'func.stub');
                $ctrlMethod .= str_replace('{name}', $method, $mc);
            }

            $ctrlMethod .= PHP_EOL . PHP_EOL;
        }

        $command = $this->getApplication()->find('make:request');
        foreach (array_unique($addRequest) as $name) {
            $a = $command->run(new ArrayInput(['name' => $name, '-m' => $input->getOption('model')]), $output);
            dump($a);
        }

        $ctrlMethod = str_replace('{model}', empty($model) ? '' : $model, $ctrlMethod);
        $tmpl = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'controller.stub');
        $tmpl = str_replace('{namespace}', $ctrlNamespace, $tmpl);
        asort($useNamespace);
        $tmpl = str_replace('{useNamespace}', implode(PHP_EOL, array_unique($useNamespace)), $tmpl);
        $tmpl = str_replace('{controllerName}', $ctrlName, $tmpl);
        $tmpl = str_replace('{namespace}', $ctrlNamespace, $tmpl);
        $tmpl = str_replace('{router}', Helper::humpToCL($onlyName), $tmpl);
        $tmpl = str_replace('{onlyName}', $onlyName, $tmpl);
        $tmpl = str_replace('{methods}', $ctrlMethod, $tmpl);

        $ctrlPath = str_replace('\\', DIRECTORY_SEPARATOR, $ctrlNamespace . DIRECTORY_SEPARATOR);
        !file_exists($ctrlPath) && @mkdir($ctrlPath, 0777, true);
        file_put_contents(base_path($ctrlPath . DIRECTORY_SEPARATOR . $ctrlName . '.php'), $tmpl);

        $output->write('<info>Successfully created the ' .$ctrlName . '</info>');
        return self::SUCCESS;
    }
}
