<?php

namespace Modules\Base\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Modules\DvUi\Services\Plugins\Toastr\Toastr;
use Modules\Seguros\Models\RepresentanteModel;
use Modules\Seguros\Models\SeguradoraModel;

abstract class BaseLivewireForm extends Component
{
    public ?RepresentanteModel $model = null;

    public array $modelAttributes = [];

    public function mount($model)
    {
        if (isset($this->model->id)) {
            $this->modelAttributes = $this->getModel()->toArray();
        }
    }

    abstract protected function modelClass():string;
    abstract protected function getModel();

    public function save()
    {
        $this->validate();
        try {
            DB::beginTransaction();

            $class = $this->getClass();
            $this->model = $this->model ?? new $class();
            $this->saveAction();
            DB::commit();
            Toastr::instance($this)->success('Item salvo')->dispatch();
        } catch (\Exception $exception) {
            DB::rollBack();
            $msg = config('app.env') == 'local'
                ? $exception->getMessage()
                : 'Não foi possível salvar o item. Tente novamente mais tarde';
            Toastr::instance($this)->error($msg)->dispatch();
        }
    }

    /**@return SeguradoraModel[]|Collection*/
    public function seguradoras(): Collection|array
    {
        return SeguradoraModel::all();
    }

    /**
     * @return string
     * @throws \Exception
     */
    protected function getClass(): string
    {
        $class = $this->modelClass();
        if (empty($class)) {
            throw new \Exception('Inform the model class');
        }
        return $class;
    }
}
