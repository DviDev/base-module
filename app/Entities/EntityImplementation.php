<?php

namespace Modules\Base\Entities;

trait EntityImplementation
{
    use Props;

    /**@return object */
    public static function new(): object
    {
        $class = static::class;
        return new $class;
    }
}
