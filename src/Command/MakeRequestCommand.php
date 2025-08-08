<?php

namespace Nasus\WebmanUtils\Command;

use Nasus\WebmanUtils\Utils\Helper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('make:request', 'Make Request')]
class MakeRequestCommand extends BaseCommand
{
    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'request name');
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
        $modelNamespace = config(sprintf('%s.database.connections.%s.model', self::ConfigPrefix, $this->connection));
        $requestNamespace = config(sprintf('%s.database.connections.%s.request', self::ConfigPrefix, $this->connection));

        $requestName = Helper::SnakeToCamel($input->getArgument('name'));
        if (!str_ends_with($requestName, 'Request')) {
            $requestName = $requestName . 'Request';
        }

        $rules = '';
        $comment = '';
        if (!empty($input->getOption('model'))) {
            $model = Helper::SnakeToCamel($input->getOption('model'));
            if (!file_exists(base_path($modelNamespace . DIRECTORY_SEPARATOR . 'do' . DIRECTORY_SEPARATOR . $model . 'Do.php'))) {
                $output->write(sprintf('<error>The %s\%s file does not exist!</error>', $modelNamespace, $model));
                return self::FAILURE;
            }

            $modelClass = sprintf('\\%s\\%s', $modelNamespace, $model);
            $modelDo = sprintf('\\%s\\do\\%sDo', $modelNamespace, $model);
            $fieldMap = $modelDo::FieldsCommentsArray;
            $typeMap = $modelDo::FieldsTypeArray;

            $tableColumns = $this->db()->select('SHOW FULL COLUMNS FROM ' . $modelClass::TABLE);
            $columnRequireMap = [];
            foreach ($tableColumns as $column) {
                $columnRequireMap[$column->Field] = $column->Null == 'NO';
            }

            foreach ($fieldMap as $field => $name) {
                if (in_array($field, ['id', 'created_at', 'updated_at'])) continue;

                $type = $typeMap[$field];
                if ($columnRequireMap[$field]) {
                    $rules .= sprintf("    #[NotBlank(message: '请填写%s')]" . PHP_EOL, $name);
                }

                $rules .= sprintf("    #[ParameterDoc(field: '%s', name: '%s', type: '%s')]" . PHP_EOL, $field, $name, $type);
                $rules .= sprintf('    public mixed $%s;' . PHP_EOL . PHP_EOL, lcfirst(Helper::SnakeToCamel($field)));

                $comment .= sprintf(' * @property %s $%s 字典类型表', $type, lcfirst(Helper::SnakeToCamel($field)));
                $comment .= PHP_EOL;
            }
        }

        $tmpl = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'request.stub');
        $tmpl = str_replace('{namespace}', $requestNamespace, $tmpl);
        $tmpl = str_replace('{fieldDoc}', rtrim($comment), $tmpl);
        $tmpl = str_replace('{requestName}', $requestName, $tmpl);
        $tmpl = str_replace('{rules}', rtrim($rules), $tmpl);

        $requestPath = str_replace('\\', DIRECTORY_SEPARATOR, $requestNamespace);
        !file_exists($requestPath) && @mkdir($requestPath, 0777, true);
        file_put_contents(base_path($requestPath . DIRECTORY_SEPARATOR . $requestName . '.php'), $tmpl);
        return self::SUCCESS;
    }
}
