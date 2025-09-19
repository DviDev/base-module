<?php

declare(strict_types=1);

namespace Modules\Base\Contracts;

interface BaseServiceProviderInterface
{
    public static function errorTypeClass(): string;
}
