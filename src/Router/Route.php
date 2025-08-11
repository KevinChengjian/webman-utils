<?php

namespace Nasus\WebmanUtils\Router;

use Nasus\WebmanUtils\Annotation\RequestMapping;
use Nasus\WebmanUtils\Utils\Helper;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionMethod;

class Route
{
    public static array $routers = [];

    /**
     * @return void
     * @throws \ReflectionException
     */
    public static function init()
    {
        self::initAppRouter();

        self::initPluginRouter();
    }

    /**
     * App Router
     *
     * @return void
     * @throws \ReflectionException
     */
    public static function initAppRouter(): void
    {
        if (!file_exists(app_path('controller'))) return;

        $controllers = static::scandir(app_path('controller'));;
        foreach ($controllers as $controller) {
            self::parseController($controller);
        }
    }

    /**
     * Plugin Router
     *
     * @return void
     * @throws \ReflectionException
     */
    public static function initPluginRouter(): void
    {
        if (!file_exists(Helper::pluginPath())) return;

        $applications = Helper::scandir(Helper::pluginPath());
        foreach ($applications as $app) {
            $path = Helper::pluginPath($app . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'controller');
            if (!file_exists($path)) continue;

            $controllers = static::scandir($path);
            foreach ($controllers as $controller) {
                self::parseController($controller, $app);
            }
        }
    }

    /**
     * 获取Controller
     *
     * @param string $path
     * @return array
     */
    public static function scanDir(string $path): array
    {
        $iterator = new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS);
        $recursiveIterator = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::SELF_FIRST);

        $controllers = [];
        foreach ($recursiveIterator as $file) {
            if (!$file->isFile()) continue;
            $controller = str_replace(base_path(), '', $file->getPathname());
            $controllers[] = str_replace('.php', '', $controller);
        }

        return $controllers;
    }

    /**
     * @param string $class
     * @param string|null $app
     * @throws \ReflectionException
     */
    public static function parseController(string $class, string $app = null): void
    {
        $ctrlRef = new ReflectionClass($class);
        $methods = $ctrlRef->getMethods(ReflectionMethod::IS_PUBLIC);

        $reqAttr = $ctrlRef->getAttributes(RequestMapping::class);
        if (empty($reqAttr) && empty($methods)) return;

        $menuAttr = empty($reqAttr[0]) ? new RequestMapping() : $reqAttr[0]->newInstance();
        $menuAttr->path = !empty($menuAttr->path)
            ? Helper::humpToCL($menuAttr->path)
            : Helper::humpToCL(str_replace('Controller', '', basename($ctrlRef->getName())));

        $methods = $ctrlRef->getMethods(ReflectionMethod::IS_PUBLIC);
        foreach ($methods as $methodRef) {
            $methodAttrs = $methodRef->getAttributes(RequestMapping::class);
            if (empty($methodAttrs)) continue;
            $methodAttr = $methodAttrs[0]->newInstance();

            $methodPath = empty($methodAttr->path) ? $methodRef->getName() : $methodAttr->path;
            $router = sprintf('%s/%s', $menuAttr->path, Helper::humpToCL($methodPath));
            $router = is_null($app) ? $router : sprintf('%s/%s', $app, ltrim($router, '/'));
            $action = sprintf("%s@%s", $ctrlRef->getName(), $methodRef->getName());

            self::$routers[$action]['router'] = $router;
            self::$routers[$action]['authCode'] = str_replace('/', '.', ltrim($router, '/'));
            self::$routers[$action]['ctrlRmRef'] = $menuAttr;
            self::$routers[$action]['methodRmRef'] = $methodAttr;

            $httpMethod = is_array($methodAttr->methods) ? $methodAttr->methods : [$methodAttr->methods];
            foreach ($httpMethod as $method) {
                $funcName = strtolower($method);
                if (in_array($funcName, ['get', 'post', 'put', 'delete', 'patch', 'head', 'options'])) {
                    \Webman\Route::$funcName($router, [$ctrlRef->getName(), $methodRef->getName()]);
                }
            }
        }
    }
}