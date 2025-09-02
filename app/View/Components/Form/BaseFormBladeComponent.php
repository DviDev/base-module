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
    ) {
    }

    public static function getDateString(Carbon|string $date): Carbon|string
    {
        return is_a($date, Carbon::class)
            ? $date->toDateString()
            : $date;
    }
}
