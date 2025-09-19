<?php

declare(strict_types=1);

namespace Modules\Base\Entities\Actions;

use Modules\Permission\Enums\Actions;

abstract class GateContract
{
    protected static string $can = '';

    public function __call(string $name, array $arguments)
    {
        return self::handle($arguments[0], $name);
    }

    public static function __callStatic(string $name, array $arguments)
    {
        return self::handle($arguments[0], $name);
    }

    public static function can(): string
    {
        self::$can = 'can:';

        return self::class;
    }

    protected static function handle(Actions|string $action, string $name): string
    {
        $str = self::$can.$name.'.'.$action->name;
        self::$can = '';

        return $str;
    }
}
