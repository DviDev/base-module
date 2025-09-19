<?php

declare(strict_types=1);

namespace Modules\Base\Livewire\Config;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Component;
use Modules\Base\Entities\Config\ConfigEntityModel;
use Modules\Base\Models\ConfigModel;
use Modules\DvUi\Services\Plugins\Toastr\Toastr;
use Modules\Person\Enums\UserType;

final class ConfigList extends Component
{
    //    use AuthorizesRequests;

    public $search;

    protected $listeners = [
        'refresh' => '$refresh',
        'search' => 'list',
        'delete' => 'delete',
    ];

    public function render(): View
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

    public function delete(): void
    {
        Toastr::instance($this)->success('Item removido');
        $this->dispatch('refresh')->self();
    }

    public function search(): void
    {
        //        $this->authorize('search', auth()->user());
        $ret = Gate::check(UserType::ADMIN->value);
    }

    public function clear(): void
    {
        $this->reset();
    }
}
