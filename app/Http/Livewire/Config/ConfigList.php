<?php

namespace Modules\Base\Http\Livewire\Config;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Modules\Base\Entities\Config\ConfigEntityModel;
use Modules\Base\Models\ConfigModel;
use Modules\DvUi\Services\Plugins\Toastr\Toastr;
use Modules\Person\Entities\User\UserType;

class ConfigList extends Component
{
//    use AuthorizesRequests;

    public $search;
    protected $listeners = [
        'refresh' => '$refresh',
        'search' => 'list',
        'delete' => 'delete'
    ];

    public function render()
    {
        return view('base::livewire.config.config-list');
    }

    /**
     * @return LengthAwarePaginator|ConfigModel[]
     */
    public function list(): LengthAwarePaginator|array
    {
        $config = ConfigEntityModel::props();

        $query = ConfigModel::query();
        if ($this->search) {
            $query->where($config->name, 'like', "%$this->search%");
        }

        return $query->paginate(10);
    }

    public function delete()
    {
        Toastr::instance($this)->success('Item removido');
        $this->dispatch('refresh')->self();
    }

    public function search()
    {
//        $this->authorize('search', auth()->user());
        $ret = Gate::check(UserType::ADMIN->value);
    }

    public function clear()
    {
        $this->reset();
    }
}
