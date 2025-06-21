<?php

namespace Modules\Base\Providers;

interface BaseServiceProviderInterface
{
    public static function errorTypeClass(): string;
}
