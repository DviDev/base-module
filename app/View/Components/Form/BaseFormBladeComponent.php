<?php

namespace Modules\Base\View\Components\Form;

use Illuminate\Support\Carbon;
use Illuminate\View\Component;
use Illuminate\View\ComponentAttributeBag;

abstract class BaseFormBladeComponent extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public ?string $label = null,
        public ?string $id = null,
        public ?string $placeholder = null,
        public ?array $attr = null,
        public bool $validate = false,
        public bool $required = false,
    ) {}

    public static function prepare(ComponentAttributeBag $attributes): void
    {
        $attrs = $attributes->get('attr');

        $items = collect($attrs)->merge($attributes->getAttributes())->filter()->forget('attr');
        if (! $items->has('id')) {
            $items->put('id', 'comp_'.now()->timestamp.\Str::random(5));
        }
        $array = $items->all();
        if (isset($array['name'])) {
            $array['name'] = __($array['name']);
        }
        if (isset($array['placeholder'])) {
            $array['placeholder'] = __($array['placeholder']);
        }
        if (isset($array['label'])) {
            $array['label'] = ucfirst(trans(strtolower($array['label'])));
        }

        $attributes->setAttributes($array);
    }

    public static function getDateString(Carbon|string $date): Carbon|string
    {
        return is_a($date, Carbon::class)
            ? $date->toDateString()
            : $date;
    }
}
