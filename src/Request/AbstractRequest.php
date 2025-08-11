<?php

namespace Nasus\WebmanUtils\Request;

use Nasus\WebmanUtils\Annotation\ParameterDoc;
use Nasus\WebmanUtils\Exception\ValidateException;
use ReflectionClass;
use ReflectionProperty;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validation;

/**
 * @method all()
 * @method input(string $name, $default = null)
 * @method only(string[] $array)
 * @method except(array $keys)
 * @method get($name = null, $default = null)
 * @method post($name = null, $default = null)
 * @method header($name = null, $default = null)
 */
abstract class AbstractRequest
{
    /**
     * @var array
     */
    protected static array $fields = [];

    protected array $rules = [];

    public function __construct()
    {
        if (!isset(self::$fields[static::class])) {
            $this->getRuleKey();
        }

        foreach (self::$fields[static::class] as $property => $field) {
            $this->rules[] = $field;
            $this->{$property} = request()->input($field);
        }

        $this->validate();
    }

    /**
     * @return void
     */
    public function getRuleKey(): void
    {
        $reflection = new ReflectionClass($this);
        if (empty(self::$fields[$reflection->getName()])) {
            foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
                $docArr = $property->getAttributes(ParameterDoc::class);
                if (empty($docArr)) continue;

                self::$fields[$reflection->getName()][$property->getName()] = $docArr[0]->newInstance()->field;
            }
        }
    }

    /**
     * @return void
     */
    public function validate(): void
    {
        $errors = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator()->validate($this);
        count($errors) > 0 && $this->failedValidation($errors);
    }

    /**
     * @param ConstraintViolationListInterface $validator
     * @return mixed
     */
    protected function failedValidation(ConstraintViolationListInterface $validator): mixed
    {
        throw new ValidateException($validator->get(0)->getMessage());
    }

    /**
     * @param array $keys
     * @return array
     */
    public function withForm(array $keys = []): array
    {
        $keys = empty($keys) ? array_keys($this->rules) : $keys;
        return \request()->only($keys);
    }

    /**
     * @param array $keys
     * @return array
     */
    public function withFormExcept(array $keys = []): array
    {
        $keys = empty($keys) ? $this->rules : array_diff($this->rules, $keys);
        return \request()->only($keys);
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        return call_user_func_array([\request(), $name], $arguments);
    }

    /**
     * @param $key
     * @return mixed|null
     */
    public function __get($key)
    {
        return \request()->input($key);
    }

    /**
     * @param $key
     * @return bool
     */
    public function __isset($key): bool
    {
        $value = $this->$key;
        return !empty($value);
    }
}