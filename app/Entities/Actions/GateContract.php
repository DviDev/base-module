<?php

namespace Modules\Base\Entities\Actions;

abstract class GateContract
{
    protected static string $can = '';

    protected static function handle(Actions|string $action, string $name): string
    {
        $str = self::$can.$name.'.'.$action->name;
        self::$can = '';

        return $str;
    }

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
}
