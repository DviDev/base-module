<?php

declare(strict_types=1);

namespace Modules\Base\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class MinWords implements ValidationRule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct(public $min, public $attribute = null) {}

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (str($value)->explode(' ')->count() < $this->min) {
            $fail('O campo '.$attribute.' deve ter o mínimo de '.$this->min.' palavras');
        }
    }
}
