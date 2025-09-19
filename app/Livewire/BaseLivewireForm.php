<?php

declare(strict_types=1);

namespace Modules\Base\Livewire;

use Illuminate\View\View;
use Modules\View\Models\ViewPageStructureModel;

final class BaseLivewireForm extends BaseLivewireComponent
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
