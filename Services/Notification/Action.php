<?php

namespace Modules\Base\Services\Notification;

class Action
{
    public function __construct(
        public ?string $text = 'Visualizar',
        public string  $url = '!#',
        public string  $type = 'info',
        public bool    $btn = false,
        public ?string $icon = null,
    )
    {
    }

    public static function new(
        ?string $text = 'Visualizar',
        string  $url = '!#',
        string  $type = 'info',
        bool    $btn = false,
        ?string $icon = null,
    ): Action
    {
        return new self($text, $url, $type, $btn, $icon);
    }
}
