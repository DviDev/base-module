<?php

namespace Modules\Base\Rules;

use Illuminate\Contracts\Validation\Rule;

class MinWords implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct(public $min, public $attribute = null) {}

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        return str($value)->explode(' ')->count() >= $this->min;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'O campo '.$this->attribute.' deve ter o mínimo de '.$this->min.' palavras';
    }
}
