<?php

namespace Modules\Base\Livewire;

use Illuminate\View\View;

class BaseForm extends BaseComponent
{
    public function render(): View
    {
        return view('base::livewire.base-form');
    }
}
