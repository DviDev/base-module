<?php

namespace Modules\Base\Contracts;

interface EntityInterface
{
    /**
     * @return array
     * @throws \ReflectionException
     */
    public static function propsArray():array;

    /**
     * return object with properties
     * @param null $alias
     * @return object
     */
    public static function props($alias = null):object;

    public function isChanged($attribute): bool;
}
