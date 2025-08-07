<?php

namespace Modules\Base\Livewire;

use Illuminate\View\View;
use Modules\View\Models\ViewPageStructureModel;

class BaseForm extends BaseComponent
{
    public function render(): View
    {
        return view('base::livewire.base-form');
    }

    public function getStructure(): ViewPageStructureModel
    {
        return $this->page->firstActiveFormStructure();
    }

    public function getExceptItems(): array
    {
        return [];
    }
}
