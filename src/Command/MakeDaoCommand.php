<?php

namespace Nasus\WebmanUtils\Command;

use Reflection;
use Nasus\WebmanUtils\Utils\Helper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('make:dao', 'Make Model')]
class MakeDaoCommand extends BaseCommand
{
    /**
     * 数据表
     * @var mixed
     */
    protected string|null $table;

    /**
     * @var array
     */
    protected array $tables = [];

    /**
     * @var array|string[]
     */
    protected array $filterMethod = ['fields', 'fieldsEx'];

    /**
     * @var array
     */
    protected array $useNamespace = [
        'use Illuminate\Database\Eloquent\Builder;',
    ];

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->addOption('table', 't', InputOption::VALUE_OPTIONAL, 'table name');
        $this->addOption('connection', 'c', InputOption::VALUE_OPTIONAL, 'database connection');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws \ReflectionException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->connection = $input->getOption('connection') ?? config(sprintf('%s.database.default', self::ConfigPrefix));
        $this->getTables($input, $output);
        if (empty($this->tables)) return self::SUCCESS;

        $namespace = config(sprintf('%s.database.connections.%s.model', self::ConfigPrefix, $this->connection));
        foreach ($this->tables as $table) {
            $tableColumns = $this->db()->select('SHOW FULL COLUMNS FROM ' . $table);
            $modelName = Helper::SnakeToCamel($table);
            $this->generativeModel($namespace, $modelName, $table, $tableColumns);
            $this->generativeModelDo($namespace, $modelName, $table, $tableColumns);
        }

        return self::SUCCESS;
    }

    /**
     * 生成模型
     *
     * @param string $namespace
     * @param string $modelName
     * @param string $table
     * @param array $tableColumns
     * @return void
     * @throws \ReflectionException
     */
    protected function generativeModel(string $namespace, string $modelName, string $table, array $tableColumns): void
    {
        $tmpl = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'model.stub');
        $tmpl = str_replace('{namespace}', $namespace, $tmpl);
        $tmpl = str_replace('{modelName}', $modelName, $tmpl);
        $tmpl = str_replace('{tableName}', $table, $tmpl);
        $tmpl = str_replace('{fieldDoc}', $this->getModelFieldDoc($tableColumns), $tmpl);

        $modelPath = str_replace('\\', DIRECTORY_SEPARATOR, $namespace);
        !file_exists($modelPath) && @mkdir($modelPath, 0777, true);

        $modelFile = base_path($modelPath . DIRECTORY_SEPARATOR . $modelName . '.php');
        $customContent = '';
        if (file_exists($modelFile)) {
            $className = str_replace('/', '\\', $namespace . DIRECTORY_SEPARATOR . $modelName);
            $tmpl = $this->modelDiffContent($className, $modelFile, $tmpl);
        } else {
            $useNamespace = array_merge($this->useNamespace, [sprintf('use %s\\do\%sDo;', $namespace, $modelName)]);
            $tmpl = str_replace('{useNamespace}', implode(PHP_EOL, $useNamespace), $tmpl);
            $tmpl = str_replace('{customMethod}', $customContent, $tmpl);
            $tmpl = str_replace('{useTrait}', '', $tmpl);
            $tmpl = str_replace('{const}', '', $tmpl);
            $tmpl = str_replace('{property}', '', $tmpl);
        }

        file_put_contents(base_path($modelPath . DIRECTORY_SEPARATOR . $modelName . '.php'), $tmpl);
    }

    /**
     * model exists content
     *
     * @param string $class
     * @param string $file
     * @param string $tmpl
     * @return string
     * @throws \ReflectionException
     */
    protected function modelDiffContent(string $class, string $file, string $tmpl): string
    {
        $ref = new \ReflectionClass($class);
        $fileContentArr = file($file);
        $fileContentStr = implode(PHP_EOL, $fileContentArr);
        if ($fileContentArr === false) return '';

        // 赋值依赖导入
        $useNamespace = [];
        for ($i = 1; $i < $ref->getStartLine(); $i++) {
            $str = rtrim($fileContentArr[$i]);
            if (!str_starts_with($str, 'use')) continue;
            $useNamespace[] = $str;
        }
        sort($useNamespace);
        $tmpl = str_replace('{useNamespace}', implode(PHP_EOL, $useNamespace), $tmpl);

        // 赋值Trait
        $traits = [];
        foreach ($ref->getTraitNames() as $trait) {
            $traits[] = basename($trait);
        }
        if (!empty($traits)) {
            $tmpl = str_replace('{useTrait}', sprintf('%s    use %s;' . PHP_EOL, PHP_EOL, implode(', ', $traits)), $tmpl);
        } else {
            $tmpl = str_replace('{useTrait}', '', $tmpl);
        }

        // 赋值常量
        $constStr = PHP_EOL;
        foreach ($ref->getReflectionConstants() as $const) {
            if ($const->getName() == 'TABLE') continue;
            if (!str_contains($fileContentStr, $const->getName())) {
                continue;
            }

            if ($const->getDeclaringClass()->getName() === $class) {
                $constStr .= '    ' . $const->getDocComment() . PHP_EOL;
                $constStr .= sprintf("    const %s = '%s';%s", $const->getName(), $const->getValue(), PHP_EOL);
            }
        }
        $tmpl = str_replace('{const}', $constStr, $tmpl);

        // 赋值属性
        $propertyStr = PHP_EOL;
        foreach ($ref->getProperties() as $property) {
            if ($property->getName() == 'table') continue;
            if (!str_contains($fileContentStr, $property->getName())) {
                continue;
            }

            if ($property->getDeclaringClass()->getName() === $class) {
                $propertyStr .= '    ' . $property->getDocComment() . PHP_EOL;
                $type = implode(' ', Reflection::getModifierNames($property->getModifiers()));

                if ($property->hasDefaultValue()) {
                    $value = $property->getDefaultValue();
                    if (is_array($value)) {
                        $propertyStr .= sprintf("    %s $%s = [%s];%s", $type, $property->getName(), implode(',', $value), PHP_EOL);
                    } else {
                        $propertyStr .= sprintf("    %s $%s = '%s';%s", $type, $property->getName(), $value, PHP_EOL);
                    }
                } else {
                    $propertyStr .= sprintf("    %s $%s;%s", $type, $property->getName(), PHP_EOL);
                }
            }
        }
        $tmpl = str_replace('{property}', $propertyStr, $tmpl);

        // 赋值已有方法
        $content = PHP_EOL;
        foreach ($ref->getMethods() as $method) {
            if ($fileContentArr === false || in_array($method->getName(), $this->filterMethod)) continue;
            if (!str_contains($fileContentStr, $method->getName() . '(')) {
                continue;
            }

            if ($method->getDeclaringClass()->getName() !== $class) continue;

            if ($method->getDocComment() !== false) {
                $content .= '    ' . $method->getDocComment() . PHP_EOL;
            }
            $methodArr = array_slice($fileContentArr, $method->getStartLine() - 1, $method->getEndLine() - $method->getStartLine() + 1);
            $content .= implode('', $methodArr) . PHP_EOL;
        }
        return str_replace('{customMethod}', $content, $tmpl);
    }

    /**
     * 生成模型Do
     *
     * @param string $namespace
     * @param string $modelName
     * @param string $table
     * @param array $tableColumns
     * @return void
     */
    protected function generativeModelDo(string $namespace, string $modelName, string $table, array $tableColumns): void
    {
        $tmpl = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'modelDo.stub');
        $tmpl = str_replace('{namespace}', $namespace, $tmpl);
        $tmpl = str_replace('{modelName}', $modelName, $tmpl);
        $tmpl = str_replace('{tableName}', $table, $tmpl);
        $tmpl = str_replace('{fieldDoc}', $this->getModelFieldDoc($tableColumns), $tmpl);

        $fieldConst = '';
        $fieldArr = [];
        $fieldCommentArr = [];
        $fieldTypeArr = [];
        foreach ($tableColumns as $column) {
            $fieldConst .= sprintf("    /**\r     * %s\r     * @var string\r     */\r", $column->Comment);
            $fieldConst .= sprintf("    const string %s = '%s';\r\r", Helper::SnakeToCamel($column->Field), $column->Field);
            $fieldArr[] = sprintf("'%s'", $column->Field);
            $comment = explode(':', trim($column->Comment));
            $fieldCommentArr[] = sprintf("'%s' => '%s'", $column->Field, empty($comment[0]) ? '' : $comment[0]);
            $fieldTypeArr[] = sprintf("'%s' => '%s'", $column->Field, Helper::dbTypeConversion($column->Type));
        }

        // 添加字段数组
        $fieldConst .= "    /**\r     * 字段数组\r     * @var array\r     */\r";
        $fieldConst .= sprintf("    const array FieldsArray = [%s];\r\r", implode(', ', $fieldArr));

        // 添加字段注释数组
        $fieldConst .= "    /**\r     * 字段描述数组\r     * @var array\r     */\r";
        $fieldConst .= sprintf("    const array FieldsCommentsArray = [\r        %s\r    ];\r", implode(",\r        ", $fieldCommentArr));

        // 添加字段类型
        $fieldConst .= "    /**\r     * 字段类型数组\r     * @var array\r     */\r";
        $fieldConst .= sprintf("    const array FieldsTypeArray = [\r        %s\r    ];\r", implode(",\r        ", $fieldTypeArr));

        $tmpl = str_replace('{fieldConst}', $fieldConst, $tmpl);

        $modelPath = str_replace('\\', DIRECTORY_SEPARATOR, $namespace . DIRECTORY_SEPARATOR . 'do');
        !file_exists($modelPath) && @mkdir($modelPath, 0777, true);
        file_put_contents(base_path($modelPath . DIRECTORY_SEPARATOR . $modelName . 'Do.php'), $tmpl);
    }

    protected function getModelFieldDoc($tableColumns): string
    {
        $fieldDoc = '';
        foreach ($tableColumns as $column) {
            $fieldDoc .= sprintf(" * @property %s $%s %s\r", Helper::dbTypeConversion($column->Type), $column->Field, trim($column->Comment));
        }
        return $fieldDoc;
    }

    /**
     * 获取数据表
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return array
     */
    protected function getTables(InputInterface $input, OutputInterface $output): array
    {
        $tableSchema = config(sprintf('%s.database.connections.%s.database', self::ConfigPrefix, $this->connection));
        $tables = $this->db()->select('SELECT * FROM information_schema.tables where TABLE_SCHEMA = ?', [$tableSchema]);
        foreach ($tables as $table) {
            $this->tables[] = $table->TABLE_NAME;
        }

        if (!empty($input->getOption('table'))) {
            $table = $input->getOption('table');
            $this->tables = in_array($table, $this->tables) ? [$table] : [];

            empty($this->tables) && $output->write(sprintf('<error>The %s table does not exist!</error>', $table));
        }

        return $this->tables;
    }
}
