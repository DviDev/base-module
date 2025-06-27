<?php

namespace Modules\Base\Livewire\Config;

use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;
use Modules\Base\Entities\Config\ConfigEntityModel;
use Modules\Base\Models\ConfigModel;
use Modules\DvUi\Services\Plugins\Toastr\Toastr;

class ConfigForm extends Component
{
    public ?ConfigModel $config;

    public $name;

    public function mount(ConfigModel $config): void
    {
        $this->config = $config;
        $this->name = $config->name;
    }

    public function render(): View
    {
        return view('base::livewire.config.config-form');
    }

    public function getRules(): array
    {
        $config = ConfigEntityModel::props('config', true);

        return [
            'name' => ['required', Rule::unique('base_configs')->ignore($this->config->id), 'max:255', 'min:3'],
            $config->value => ['required', 'max:255'],
            $config->description => 'nullable',
        ];
    }

    public function save(): void
    {
        $this->validate();

        $this->config->name = $this->name;
        if (! $this->config->id) {
            $this->config->user_id = auth()->user()->id;
            $this->config->default = false;
        }
        $this->config->save();

        if (! $this->config->wasRecentlyCreated) {
            Toastr::instance($this)->preventDuplicates()->success('Item salvo');

            return;
        }
        session()->flash('success', 'Item salvo.');
        $this->redirectRoute('admin.config', $this->config->id);
    }
}
