<?php

namespace Modules\Base\Contracts;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Modules\DvUi\Services\Plugins\Toastr\Toastr;

abstract class BaseLivewireForm extends Component
{
    public $model = null;

    public array $modelAttributes = [];

    public function mount($model)
    {
        if (isset($this->model->id)) {
            $this->modelAttributes = $this->getModel()->toArray();
        }
    }

    abstract protected function modelClass():string;
    abstract protected function getModel();

    abstract public function validationAttributes();

    public function save()
    {
        try {
            $this->validate();

            DB::beginTransaction();

            $class = $this->getClass();
            $this->model = $this->model ?? new $class();
            $this->saveAction();
            DB::commit();
            Toastr::instance($this)->success('Item salvo');
        } catch (ValidationException $exception) {
            Toastr::instance($this)->error($exception->getMessage());
            throw $exception;
        } catch (\Exception $exception) {
            DB::rollBack();
            throw_if(config('app.env') == 'local', $exception);

            Toastr::instance($this)->error('Não foi possível salvar o item. Tente novamente mais tarde');
        }
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
