<?php

declare(strict_types=1);

namespace Modules\Base\Contracts;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Modules\DvUi\Services\Plugins\Toastr\Toastr;

abstract class BaseLivewireFormContract extends Component
{
    public ?Model $model = null;

    public array $modelAttributes = [];

    abstract protected function modelClass(): string;

    abstract protected function getModel(): ?BaseModelInterface;

    abstract public function validationAttributes(): array;

    public function mount($model): void
    {
        if (isset($this->model->id)) {
            $this->modelAttributes = $this->getModel()->toArray();
        }
    }

    public function save(): void
    {
        try {
            $this->validate();

            DB::beginTransaction();

            $class = $this->getClass();
            $this->model = $this->model ?? new $class;
            $this->saveAction();
            DB::commit();
            Toastr::instance($this)->success('Item salvo');
        } catch (ValidationException $exception) {
            Toastr::instance($this)->error($exception->getMessage());
            throw $exception;
        } catch (Exception $exception) {
            DB::rollBack();
            throw_if(config('app.env') === 'local', $exception);

            Toastr::instance($this)->error('Não foi possível salvar o item. Tente novamente mais tarde');
        }
    }

    /**
     * @throws Exception
     */
    protected function getClass(): string
    {
        $class = $this->modelClass();
        if (empty($class)) {
            throw new Exception('Inform the model class');
        }

        return $class;
    }
}
