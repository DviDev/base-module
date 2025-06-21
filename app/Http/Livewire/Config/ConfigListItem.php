<?php

namespace Modules\Base\Http\Livewire\Config;

use Illuminate\View\View;
use Livewire\Component;
use Modules\Base\Models\ConfigModel;

class ConfigListItem extends Component
{
    public ConfigModel $config;

    public function render(): View
    {
        return view('base::livewire.config.config-list-item');
    }

    public function delete(): void
    {
        $this->config->delete();
    }
}
