<?php

namespace Modules\Base\Entities;

use ReflectionClass;

trait Props
{
    /**
     * @return array
     */
    public static function propsArray(bool $ready = false): array
    {
        $reflectionClass = new ReflectionClass(static::class);
        $namespace = $reflectionClass->getNamespaceName();
        $className = $reflectionClass->getShortName();
        $entity = str($className)->headline()->explode(' ')->first();
        $trait_props_exists = collect($reflectionClass->getTraitNames())->some($namespace . '\\' . $entity . 'Props');
        $reflectionClass = !$trait_props_exists
            ? $reflectionClass
            : new ReflectionClass($namespace . '\\' . $entity . 'Props');
        $doc = ($reflectionClass)->getDocComment();
        $property_type = '@property ';
//        if (in_array(EntityModelInterface::class, class_implements(static::class))) {
//        } else {
//            $property_type = '@property-read ';
//        }
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
    public static function props($alias = null, $force = null): object
    {
        $key = static::class . '-props' . ($alias ? '_' . $alias : '');
        $getProps = function () use ($alias) {
            $class = static::class;

            $props = [];
            foreach (static::propsArray() as $name) {
                $props[$name] = ($alias ? ($alias . '.') : '') . $name;
            }
            $obj = new $class($props);
            foreach ($props as $name) {
                $obj->{$name} = $name;
            }
            return $obj;
        };
        if ($force) {
            $props = $getProps();
            cache()->put($key, $props);
            return $props;
        }
        return cache()->rememberForever($key, $getProps);
    }
}
