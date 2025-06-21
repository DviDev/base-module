<?php

namespace Modules\Base\Livewire;

use Illuminate\View\View;
use Modules\Base\Http\Livewire\BaseComponent;

class BaseForm extends BaseComponent
{
    public function render(): View
    {
        return view('base::livewire.base-form');
    }
}
