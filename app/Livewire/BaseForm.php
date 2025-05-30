<?php

namespace Modules\Base\Livewire;

use Modules\Base\Http\Livewire\BaseComponent;

class BaseForm extends BaseComponent
{
    public function render()
    {
        return view('base::livewire.base-form');
    }
}
