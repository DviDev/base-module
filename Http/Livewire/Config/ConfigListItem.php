<?php

namespace Modules\Base\Http\Livewire\Config;

use Livewire\Component;
use Modules\Base\Models\ConfigModel;

class ConfigListItem extends Component
{
    public ConfigModel $config;

    public function render()
    {
        return view('base::livewire.config.config-list-item');
    }

    public function delete()
    {
        $this->config->delete();
    }
}
