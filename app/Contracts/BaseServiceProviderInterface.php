<?php

namespace Modules\Base\Contracts;

interface BaseServiceProviderInterface
{
    public static function errorTypeClass(): string;
}
