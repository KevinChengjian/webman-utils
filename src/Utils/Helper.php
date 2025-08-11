<?php

namespace Nasus\WebmanUtils\Utils;

/**
 * 辅助函数
 */
class Helper
{
    /**
     * @param string $path
     * @return string
     */
    public static function pluginPath(string $path = ''): string
    {
        return base_path('plugin' . DIRECTORY_SEPARATOR . $path);
    }

    /**
     * 驼峰转下划线
     *
     * @param string $camel
     * @return string
     */
    public static function humpToUL(string $camel): string
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', "$1" . '_' . "$2", $camel));
    }

    /**
     * 驼峰转中划线
     *
     * @param string $camel
     * @return string
     */
    public static function humpToCL(string $camel): string
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', "$1" . '-' . "$2", $camel));
    }

    /**
     * 数据库字段类型转PHP类型
     *
     * @param string $type
     * @return string
     */
    public static function dbTypeConversion(string $type): string
    {
        $type = preg_replace('/\(.*$/', '', $type);
        return match ($type) {
            'tinyint', 'int', 'bigint' => 'int',
            'char', 'varchar', 'tinytext', 'longtext', 'enum', 'date', 'datetime', 'timestamp', 'time' => 'string',
            'decimal', 'double', 'float' => 'float',
        };
    }

    /**
     * 下划线转大驼峰
     * @param string $str
     * @return string
     */
    public static function SnakeToCamel(string $str): string
    {
        $wordStr = ucwords(str_replace('_', ' ', $str));
        return str_replace(' ', '', $wordStr);
    }

    /**
     * 下划线转小驼峰
     * @param string $str
     * @return string
     */
    public static function SnakeToMinCamel(string $str): string
    {
        $wordStr = ucwords(str_replace('_', ' ', $str));
        return lcfirst(str_replace(' ', '', $wordStr));
    }

    /**
     * 格式化树结构
     *
     * @param array $data
     * @param int $pid
     * @param string $name
     * @param string $idName
     * @return array
     */
    public static function tree(array $data, int $pid = 0, string $name = 'pid', $idName = 'id'): array
    {
        $tree = [];
        foreach ($data as $item) {
            if ($item[$name] == $pid) {
                $item['children'] = self::tree($data, $item[$idName], $name, $idName);
                $tree[] = $item;
            }
        }

        return $tree;
    }

    /**
     * 获取树结构最后一级数据
     * @param $data
     * @return array
     */
    public static function getTreeLastChildren($data): array
    {
        $children = [];
        foreach ($data as $item) {
            if (empty($item['children'])) {
                $children[] = $item;
            } else {
                $children = array_merge($children, self::getTreeLastChildren($item['children']));
            }
        }

        return $children;
    }

    /**
     * 获取目录下面的文件
     *
     * @param string $path
     * @return array
     */
    public static function scanDir(string $path): array
    {
        return array_filter(scandir($path), function ($dir) {
            return !($dir == '.' || $dir == '..');
        });
    }
}