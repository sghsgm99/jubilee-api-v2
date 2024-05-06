<?php

namespace App\Models\Enums;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use ReflectionClass;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * Class Enumerate.
 * @property $key
 * @property $value
 *
 */
abstract class Enumerate implements \JsonSerializable, CastsAttributes
{
    public $key;
    public $value;

    /**
     * @param Model $model
     * @param string $key
     * @param mixed $value
     * @param array $attributes
     * @return mixed
     */
    public function get($model, string $key, $value, array $attributes)
    {
        if ($value instanceof Enumerate) {
            return $value;
        }

        return static::memberByValue($value);
    }

    /**
     * @param Model $model
     * @param string $key
     * @param mixed|Enumerate $enum
     * @param array $attributes
     * @return array
     */
    public function set($model, string $key, $enum, array $attributes)
    {
        if ($enum instanceof Enumerate) {
            return $enum->value;
        }

        return $enum;
    }

    /**
     * @param mixed $value
     */
    public function setValue($value): void
    {
        $this->value = $value;
    }

    /**
     * @param mixed $key
     */
    public function setKey($key): void
    {
        $this->key = $key;
    }

    public static function __callStatic($name, $arguments)
    {
        /** @var \ReflectionClassConstant $const */
        $const = self::getConstants()->firstWhere('name', '=', $name);
        if ($const) {
            return self::memberByValue($const->getValue());
        }

        throw new MethodNotAllowedHttpException([], $name . ' is not a valid const in the Enumeration: ' . __CLASS__);
    }

    public function isUndefined()
    {
        return $this->getLabel() === 'UNDEFINED';
    }

    public function isNotUndefined()
    {
        return !$this->isUndefined();
    }

    /**
     * @return \ReflectionClassConstant[]|Collection
     * @throws \ReflectionException
     */
    public static function getConstants()
    {
        return collect((new ReflectionClass(get_called_class()))->getReflectionConstants());
    }

    public static function clientData()
    {
        $clientData = [];
        foreach (self::getConstants() as $constant) {
            array_push($clientData, [
                'label' => str_replace('_', ' ', $constant->getName()),
                'value' => $constant->getValue(),
            ]);
        }

        return $clientData;
    }


    public static function clientConsts()
    {
        $clientData = [];
        foreach (self::getConstants() as $constant) {
            $property = Str::studly($constant->getName());
            $clientData[$property] = [
                'key' => $property,
                'label' => str_replace('_', ' ', $constant->getName()),
                'value' => $constant->getValue(),
            ];
        }

        return $clientData;
    }

    public static function keys()
    {
        $keys = [];
        foreach (self::getConstants() as $constant) {
            array_push($keys, $constant->getName());
        }

        return $keys;
    }

    public static function enums()
    {
        $enums = [];
        foreach (self::getConstants() as $constant) {
            $className = get_called_class();
            $enum = new $className();
            $enum->setKey($constant->getName());
            $enum->setValue($constant->getValue());
            $enums[] = $enum;
        }

        return $enums;
    }


    public static function members()
    {
        $members = [];
        foreach (self::getConstants() as $constant) {
            $members[str_replace('_', ' ', $constant->getName())] = $constant->getValue();
        }

        return $members;
    }

    /**
     * @param $value
     * @return static
     */
    public static function memberByValue($value)
    {
        /*
         * This is here to allow for setting the Enum directly on the Model and to bypass the following checks
         * if the value is a class instead of a data type.
         */
        if (is_object($value)) {
            return $value;
        }

        $className = get_called_class();
        $members = call_user_func($className . '::members');

        /**
         * This will prevent null from resolving a value that is should not be resolving
         */
        if ($value === null || $value === '') {
            $enum = new $className();
            $enum->setKey('UNDEFINED');
            $enum->setValue(null);
            return $enum;
        }

        if (in_array($value, $members)) {
            $enum = new $className();
            $enum->setKey(array_search($value, $members));
            $enum->setValue($value);
            return $enum;
        }
    }

    /**
     * @param $key
     * @return static
     */
    public static function memberByKey($key)
    {
        /*
         * This is here to allow for setting the Enum directly on the Model and to bypass the following checks
         * if the value is a class instead of a data type.
         */
        if (is_object($key)) {
            return $key;
        }

        $className = get_called_class();
        $members = call_user_func($className . '::members');

        if (in_array($key, array_keys($members))) {
            $value = array_values($members)[array_search($key, array_keys($members))];

            $enum = new $className();
            $enum->setKey($key);
            $enum->setValue($value);
            return $enum;
        }
    }

    public function isNot(self $enum, bool $strict = false)
    {
        return !$this->is($enum, $strict);
    }

    public function is(self $enum, bool $strict = false)
    {
        if ($not_same_type = get_class($this) !== get_class($enum)) {
            return false;
        }

        /**
         * Set Strict check if the value is empty
         */
        if ($this->value === null || $this->value === '') {
            $strict = true;
        }

        if ($strict and $not_exactly_the_same_value = $this->value !== $enum->value) {
            return false;
        }

        if ($not_the_same_value = $this->value != $enum->value) {
            return false;
        }

        return true;
    }

    /**
     * @return mixed
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @return mixed
     */
    public function getLabel()
    {
        return str_replace('_', ' ', $this->key);
    }

    /**
     * This is here to allow for persisting data to the database when the enum is set on the Model.
     *
     * @return string
     */
    public function __toString()
    {
        if (is_int($this->value)) {
            return (string)$this->value;
        }

        return (string)$this->value;
    }

    /**
     * Specify data which should be serialized to JSON.
     * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        if (is_numeric($this->value)) {
            return (int)$this->value;
        }

        return $this->value;
    }
}
