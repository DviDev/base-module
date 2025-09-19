<?php

declare(strict_types=1);

namespace Modules\Base\Contracts;

use ReflectionException;

interface EntityInterface
{
    /**
     * @throws ReflectionException
     */
    public static function propsArray(): array;

    /**
     * return object with properties
     *
     * @param  null  $alias
     */
    public static function props($alias = null): object;

    public function isChanged(string $attribute): bool;
}
