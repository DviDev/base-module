<?php

namespace Modules\Base\Entities;

use Modules\Base\Contracts\EntityModelInterface;
use ReflectionClass;

trait EntityImplementation
{
    /**
     * @return array
     */
    public static function propsArray(bool $ready  = false):array
    {
        $doc = (new ReflectionClass(static::class))->getDocComment();
        if (in_array(EntityModelInterface::class, class_implements(static::class))) {
            $property_type = '@property ';
        } else {
            $property_type = '@property-read ';
        }
        $lines = explode("\n", $doc);

        $props = [];
        foreach ($lines as $line) {
            if (!str_contains($line, $property_type)) {
                continue;
            }
            $props[] = trim(substr($line, strpos($line, '$') + 1));
        }
        return $props;
    }

    /**
     * return object with properties
     * @param null $alias
     * @param null $force
     * @return object
     */
    public static function props($alias = null, $force = null):object
    {
        $key = static::class .'-props'.($alias ? '_'.$alias : '');
        $getProps = function () use ($alias) {
            $class = static::class;

            $array = [];
            foreach (static::propsArray() as $prop) {
                $array[$prop] = ($alias ? ($alias . '.') : '') . $prop;
            }
            return new $class($array);
        };
        if ($force) {
            $props = $getProps();
            cache()->put($key, $props);
            return $props;
        }
        return cache()->rememberForever($key, $getProps);
    }

    /**@return object */
    public static function new(): object
    {
        $class = static::class;;
        return new $class;
    }
}
