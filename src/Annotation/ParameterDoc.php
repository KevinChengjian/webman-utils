<?php

namespace Nasus\WebmanUtils\Annotation;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class ParameterDoc
{
    public string $field;

    public string $name;

    public string $type;

    public string $desc;

    public array $doc;

    public function __construct(string $field, string $name, string $type = '', string $desc = '', array $doc = [])
    {
        $this->field = $field;
        $this->name = $name;
        $this->type = $type;
        $this->desc = $desc;
        $this->doc = $doc;
    }
}