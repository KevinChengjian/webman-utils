<?php

namespace Nasus\WebmanUtils\Utils;

class OpenApiDoc
{
    /**
     * 解析Api文档
     * @param $path
     * @return void
     */
    public static function analysis($path): void
    {
        $controllers = Helper::scanDir(Helper::pluginPath('admin' . DIRECTORY_SEPARATOR . 'controller'));
        foreach ($controllers as $controller) {

        }
    }
}