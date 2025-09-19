<?php

declare(strict_types=1);

namespace Modules\Base\Database\Seeders;

final class SeederEventDTO
{
    private string $event;

    private array $parameters;

    private function __construct() {}

    public static function event(string $event): static
    {
        $new = new self;
        $new->event = $event;

        return $new;
    }

    public function param(string $name, mixed $value): static
    {
        $this->parameters[$name] = $value;

        return $this;
    }

    public function params(array $array): static
    {
        $this->parameters = $array;

        return $this;
    }

    public function payload(): array
    {
        return $this->parameters;
    }

    public function class(): string
    {
        return $this->event;
    }
}
