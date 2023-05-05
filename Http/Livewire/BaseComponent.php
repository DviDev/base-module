<?php

namespace Modules\Base\Http\Livewire;

use Carbon\Carbon;
use Exception;
use Livewire\Component;
use Modules\Base\Models\BaseModel;
use Modules\LaravelProgram\Commands\DviRequestMakeCommand;
use Modules\LaravelProgram\Models\ModuleTableModel;
use Modules\ViewStructure\Models\ViewPageModel;
use Modules\ViewStructure\Models\ViewStructureColumnComponentModel;
use Modules\ViewStructure\Models\ViewStructureRowModel;

class BaseComponent extends Component
{
    public BaseModel $model;
    public array $values = [];

    public ViewPageModel $page;

    protected $visible_rows;

    public function mount(BaseModel $model)
    {
        $this->model = $model;
        $this->values['dates'] = [];
        foreach ($model->attributesToArray() as $attribute => $value) {
            if (is_a($model->{$attribute}, Carbon::class)) {
                /**@var Carbon $value*/
                $value = $model->{$attribute};
                $this->values['dates'][$attribute] = ['date' => $value->format('Y-m-d'), 'time' => $value->format('H:i')];
            }
        }
    }

    public function render()
    {
        $this->getRows();

        return view('viewstructure::components.form.base-form');
    }

    public function getRows(): array
    {
        /**@var ModuleTableModel $table*/
        $table = ModuleTableModel::query()->where('name', $this->model->getTable())->first();
        $this->page = $table->pages->first();

        $fn = function() {
            $visible_rows = [];
            /**@var ViewStructureRowModel $row */
            foreach ($this->page->rows as $row) {
                $contain = false;
                foreach ($row->columns as $column) {
                    /**@var ViewStructureColumnComponentModel $component */
                    $component = $column->components->first();
                    $contain = collect([
                        'id',
                        'created_at',
                        'updated_at',
                        'deleted_at'
                    ])->some($component->attribute->name);
                    if ($contain) {
                        break;
                    }

                }
                if ($contain) {
                    continue;
                }
                $visible_rows[$row->id] = $row;
            }

            return $visible_rows;
        };

        $this->visible_rows = $this->visible_rows ?: $fn();

        return $this->visible_rows;
    }

    public function getRules()
    {
        return (new DviRequestMakeCommand)->getRules($this->model->getTable(), 'save', $this->model);
    }

    public function save()
    {
        $this->validate();
        foreach ($this->values['dates'] as $property => $values) {
            $this->model->{$property} = $values['date'].' '.$values['time'];
        }
        $this->model->save();
        session()->flash('success', __('the data has been saved'));

        return redirect()
            ->to(url()->previous());
    }

    public function getReferencedTableData(ViewStructureColumnComponentModel $component): array
    {
        try {
            if ($component->attribute->type == 6 && $component->attribute->items) {
                return str($component->attribute->items)->explode(',')->all();
            }
            return \DB::table($component->attribute->referenced_table_name)
                ->get(['id', 'name as value'])
                ->all();

        } catch (Exception $e) {
            throw new Exception("Está faltando fk ao atributo ".$component->attribute->name);
        }
    }

    public function updatePropertyValue($type, $property, $key, $value): void
    {
        $this->values[$type][$property][$key] = $value;
    }
}
