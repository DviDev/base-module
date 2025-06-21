<?php

namespace Modules\Base\Contracts;

interface EntityInterface
{
    /**
     * @throws \ReflectionException
     */
    public static function propsArray(): array;

    /**
     * return object with properties
     *
     * @param  null  $alias
     */
    public static function props($alias = null): object;

    public function isChanged($attribute): bool;
}
