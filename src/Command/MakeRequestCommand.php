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
        parent::configure();
        $this->addArgument('name', InputArgument::REQUIRED, 'request name');
        $this->addOption('model', 'm', InputOption::VALUE_OPTIONAL, 'model name');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initConf($input);
        $this->makeBaseRequest();

        $name = Helper::SnakeToCamel($input->getArgument('name'));
        $name = str_ends_with($name, 'Request') ? $name : $name . 'Request';

        $module = $this->module($name);
        $requestNamespace = empty($module) ? $this->requestNamespace : sprintf('%s\\%s', $this->requestNamespace, $module);
        $requestName = basename($name);

        $rules = '';
        $comment = '';
        if (!empty($input->getOption('model'))) {
            $model = Helper::SnakeToCamel($input->getOption('model'));
            if (!file_exists(base_path($this->modelNamespace . DIRECTORY_SEPARATOR . 'do' . DIRECTORY_SEPARATOR . $model . 'Do.php'))) {
                $output->write(sprintf('<error>The %s\%s file does not exist!</error>', $this->modelNamespace, $model));
                return self::FAILURE;
            }

            $modelClass = sprintf('\\%s\\%s', $this->modelNamespace, $model);
            $modelDo = sprintf('\\%s\\do\\%sDo', $this->modelNamespace, $model);
            $fieldMap = $modelDo::FieldsCommentsArray;
            $typeMap = $modelDo::FieldsTypeArray;

            $tableColumns = $this->db()->select('SHOW FULL COLUMNS FROM ' . $modelClass::TABLE);
            $columnRequireMap = [];
            foreach ($tableColumns as $column) {
                $columnRequireMap[$column->Field] = $column->Null == 'NO';
            }

            foreach ($fieldMap as $field => $name) {
                if (in_array($field, ['id', 'created_at', 'updated_at', 'deleted_at', 'create_by', 'update_by'])) continue;

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
        $tmpl = str_replace('{baseNamespace}', $this->requestNamespace, $tmpl);
        $tmpl = str_replace('{fieldDoc}', rtrim($comment), $tmpl);
        $tmpl = str_replace('{requestName}', $requestName, $tmpl);
        $tmpl = str_replace('{rules}', rtrim($rules), $tmpl);

        $requestPath = str_replace('\\', DIRECTORY_SEPARATOR, $requestNamespace);
        !file_exists($requestPath) && @mkdir($requestPath, 0777, true);
        file_put_contents(base_path($requestPath . DIRECTORY_SEPARATOR . $requestName . '.php'), $tmpl);
        return self::SUCCESS;
    }

    /**
     * 检测是否待基础验证类
     * @return void
     */
    public function makeBaseRequest(): void
    {
        $path = sprintf('%s\\AbstractRequest.php', $this->requestNamespace);
        $path = str_replace('\\', DIRECTORY_SEPARATOR, $path);
        if (file_exists($path)) return;

        !file_exists($path) && @mkdir(dirname($path), 0777, true);

        $content = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'abstractRequest.stub');
        $content = str_replace('{namespace}', $this->requestNamespace, $content);
        file_put_contents(base_path($path), $content);
    }
}
