<?php

namespace Nasus\WebmanUtils\Annotation;

use Attribute;
use Nasus\WebmanUtils\Enums\AuthInterface;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class RequestMapping
{
    /**
     * router name
     * @var string
     */
    public string $name;

    /**
     * router path
     * @var string
     */
    public string $path;

    /**
     * http methods
     * @var string|array
     */
    public string|array $methods;

    /**
     * router description
     * @var string
     */
    public string $desc;

    /**
     * auth
     * @var AuthInterface|null
     */
    public ?AuthInterface $auth;

    /**
     * @param string $name
     * @param string $path
     * @param array|string $methods
     * @param string $desc
     * @param AuthInterface|null $auth
     */
    public function __construct(string $name = '', string $path = '', array|string $methods = ['get', 'post'], string $desc = '', AuthInterface|null $auth = null)
    {
        $this->name = $name;
        $this->path = $path;
        $this->methods = $methods;
        $this->desc = $desc;
        $this->auth = $auth;
    }
}