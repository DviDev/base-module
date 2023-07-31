<?php

namespace Modules\Base\Http\Livewire;

use Carbon\Carbon;
use Exception;
use Livewire\Component;
use Modules\Base\Models\BaseModel;
use Modules\DBMap\Commands\DviRequestMakeCommand;
use Modules\DBMap\Domains\ModuleTableAttributeTypeEnum;
use Modules\DBMap\Models\ModuleTableModel;
use Modules\View\Models\ElementModel;
use Modules\View\Models\ViewPageModel;
use Modules\View\Models\ViewPageStructureModel;

abstract class BaseComponent extends Component
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
                /**@var Carbon $value */
                $value = $model->{$attribute};
                $this->values['dates'][$attribute] = ['date' => $value->format('Y-m-d'), 'time' => $value->format('H:i')];
            }
        }
    }

    public function render()
    {
        return view('viewstructure::components.form.base-form');
    }

    public function getElements(): array
    {
        /**@var ModuleTableModel $table */
        $table = ModuleTableModel::query()->where('name', $this->model->getTable())->first();
        $this->page = $table->pages->first();

        $fn = function () {
            $visible_rows = [];
            /**@var ViewPageStructureModel $structure */
            $structure = $this->page->structures()->whereNotNull('active')->first();
            $elements = $structure->elements;
            /**@var ElementModel $element */
            foreach ($elements as $element) {
                $contain = false;
//                foreach ($element->columns as $column) {
//                    /**@var ViewStructureColumnComponentModel $component */
//                    $component = $column->components->first();
//                    if (!$component->attribute) {
//                        continue;
//                    }
//                    $contain = collect([
//                        'id',
//                        'created_at',
//                        'updated_at',
//                        'deleted_at'
//                    ])->some($component->attribute->name);
//                    if ($contain) {
//                        break;
//                    }
//                }
                if ($contain) {
                    continue;
                }
                //avoid this attributes
                /*[
                    'id',
                    'created_at',
                    'updated_at',
                    'deleted_at'
                ]*/
                $visible_rows[$element->id] = $element;
            }

            return $visible_rows;
        };

        $this->visible_rows = $this->visible_rows ?: $fn();

        return $this->visible_rows;
    }

    /**@return ElementModel[] */
    public function elements()
    {
//        cache()->delete('elements');
        /**@var ModuleTableModel $table */
        $table = ModuleTableModel::query()->where('name', $this->model->getTable())->first();
        $this->page = $table->pages->first();
        $fn = function () {
            /**@var ViewPageStructureModel $structure */
            $structure = $this->page->structures()->whereNotNull('active')->first();
            $elements = $structure->elements()->get()->filter(function (ElementModel $e) {
                return !$e->attribute || !in_array($e->attribute->name, ['id', 'created_at', 'updated_at', 'deleted_at']);
            });
            $children = collect($elements);
            foreach ($elements as $element) {
                $element_children = $element->allChildren->filter(function (ElementModel $e) {
                    return !$e->attribute || !in_array($e->attribute->name, [
                            'id',
                            'created_at',
                            'updated_at',
                            'deleted_at'
                        ]);
                })->all();
                $children->merge($element_children);
            }
            return $children;
        };
        return cache()->rememberForever('elements', $fn);
    }

    public function getRules()
    {
        $cache_key = 'model-' . $this->model->id . '-' . auth()->user()->id;

        return cache()->remember($cache_key, now()->addMinutes(30), function () {
            return (new DviRequestMakeCommand)->getRules($this->model->getTable(), 'save', $this->model);
        });
    }

    public function save()
    {
        $this->validate();
        foreach ($this->values['dates'] as $property => $values) {
            $this->model->{$property} = $values['date'] . ' ' . $values['time'];
        }
        $this->model->save();
        session()->flash('success', __('the data has been saved'));

        return redirect()
            ->to(url()->previous());
    }

    public function getReferencedTableData(ElementModel $element): array
    {
        try {
            if ($element->attribute->typeEnum() == ModuleTableAttributeTypeEnum::ENUM && $element->attribute->items) {
                return str($element->attribute->items)->explode(',')->all();
            }
            return \DB::table($element->attribute->referenced_table_name)
                ->get(['id', 'name as value'])
                ->all();

        } catch (Exception $e) {
            throw new Exception("Está faltando fk ao atributo " . $element->attribute->name);
        }
    }

    public function updatePropertyValue($type, $property, $key, $value): void
    {
        $this->values[$type][$property][$key] = $value;
    }
}
