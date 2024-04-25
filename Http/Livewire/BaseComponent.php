<?php

namespace Modules\Base\Http\Livewire;

use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Modules\Base\Models\BaseModel;
use Modules\DBMap\Commands\DviRequestMakeCommand;
use Modules\DBMap\Domains\ModuleTableAttributeTypeEnum;
use Modules\DBMap\Models\ModuleTableModel;
use Modules\DvUi\Services\Plugins\Toastr\Toastr;
use Modules\Project\Models\ProjectEntityAttributeModel;
use Modules\View\Models\ElementModel;
use Modules\View\Models\ModuleEntityPageModel;
use Modules\View\Models\ViewPageStructureModel;

abstract class BaseComponent extends Component
{
    public ?BaseModel $model;
    public array $values = [];
    public ModuleEntityPageModel $page;
    protected $visible_rows;
    protected $listeners = ['refresh' => '$refresh'];

    public function mount()
    {
        if (\Request::routeIs('view.entity.page.form')) {
            $this->page = $this->model;
        } else {
            /**@var ModuleTableModel $table */
            $table = ModuleTableModel::query()->where('name', $this->model->getTable())->first();
            $this->page = $table->pages()->where('route', 'like', '%.form')->get()->first();
        }
        $fn = fn($value) => toBRL($value);
        $this->transformValues($fn);
        $this->values['dates'] = [];
        foreach ($this->model->attributesToArray() as $attribute => $value) {
            if (is_a($this->model->{$attribute}, Carbon::class)) {
                /**@var Carbon $value */
                $value = $this->model->{$attribute};
                $this->values['dates'][$attribute] = ['date' => $value->format('Y-m-d'), 'time' => $value->format('H:i')];
            }
        }
    }

    protected function transformValues($fn)
    {
        /**@var ViewPageStructureModel $structure */
        $structure = $this->page->structures()->whereNotNull('active')->first();
        $attributes = $structure->elements()->whereNotNull('attribute_id')->join('dbmap_module_table_attributes as attribute', 'attribute.id', 'attribute_id')
            ->whereHas('attribute', function (Builder $query) {
                $query->where('type', 4);
            })
            ->pluck('attribute.name')->all();
        foreach ($attributes as $attribute) {
            if (empty($this->model->{$attribute})) {
                continue;
            }
            $this->model->{$attribute} = $fn($this->model->{$attribute});
        }
    }

    public function elements(): Collection|array
    {
        /**@var ViewPageStructureModel $structure */
        $structure = $this->page->structures()->whereNotNull('active')->first();
        $cache_key = 'structure.' . $structure->id . '.elements';
//        cache()->delete($cache_key);
        return cache()->remember($cache_key, 3600, function () use ($structure) {
            $elements = $structure->elements()->with(['allChildren', 'properties'])->get()->filter(function (ElementModel $e) {
                return empty($e->attribute) || !in_array($e->attribute->name, ['id', 'created_at', 'updated_at', 'deleted_at']);
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
        });
    }

    public function render()
    {
        return view('base::livewire.base-form');
    }

    public function getRules()
    {
        $cache_key = 'model-' . $this->model->id . '-' . auth()->user()->id;
        $ttl = now()->addMinutes(30);
        $rules = cache()->remember($cache_key, $ttl, function () {
            return (new DviRequestMakeCommand)->getRules($this->model->getTable(), 'save', $this->model);
        });
        return $rules;
    }

    public function save()
    {
        try {
            $fn = fn($value) => toUS($value);
            $this->transformValues($fn);
            $this->validate();
            foreach ($this->values['dates'] as $property => $values) {
                $this->model->{$property} = $values['date'] . ' ' . $values['time'];
            }
            $this->model->save();
            if ($this->model->wasRecentlyCreated) {
                session()->flash('success', str(__('base.the data has been saved'))->ucfirst());
                session()->flash('only_toastr');
                $route = route($this->page->route, $this->model->id);
                $this->redirect($route, navigate: true);
                return;
            }
            $fn = fn($value) => toBRL($value);
            $this->transformValues($fn);
            Toastr::instance($this)->success(str(__('base.the data has been saved'))->ucfirst());
        } catch (ValidationException $exception) {
            $fn = fn($value) => toBRL($value);
            $this->transformValues($fn);
            Toastr::instance($this)->error($exception->getMessage())->dispatch();
            throw $exception;
        } catch (Exception $exception) {
            if (config('app.env') == 'local') {
                throw $exception;
            }
            Toastr::instance($this)->error('Não foi possível salvar o item')->dispatch();
        }
    }

    public function projectAttribute($property_name, $entity_id)
    {
        return ProjectEntityAttributeModel::query()
            ->where('entity_id', $entity_id)
            ->where('name', $property_name)
            ->get(['reference_view_name'])->first();
    }

    public function getReferencedTableData(ElementModel $element, ProjectEntityAttributeModel $projectAttribute): array|LengthAwarePaginator
    {
        if ($element->attribute->typeEnum() == ModuleTableAttributeTypeEnum::ENUM && $element->attribute->items) {
            return str($element->attribute->items)->explode(',')->all();
        }
        $columns = ['id'];
        $referenced_table_name = $element->attribute->referenced_table_name;
        $table = ModuleTableModel::query()->where('name', $referenced_table_name)->first();
        $name_exists = $table->attributes()->where('name', 'name')->exists();
        $nome_exists = $table->attributes()->where('name', 'name')->exists();
        if ($name_exists) {
            $columns[] = 'name as value';
        } elseif ($nome_exists) {
            $columns[] = 'nome as value';
        } else {
            $columns[] = 'id as value';
        }
        $entity_name = str($table->entityObj->title);
        $module = $entity_name->explode(' ')->first();
        $entity_name = $entity_name->explode(' ')->filter(fn($i) => $i !== $module)->join('\\');
        $entity_name = str($entity_name)->singular()->value();
        /**@var BaseModel $model_class */
        $model_class = "Modules\\$module\\Models\\$entity_name" . 'Model';
        if (class_exists($model_class)) {
            $items = $model_class::query();
            if ($projectAttribute->reference_view_name) {
                $str = str($projectAttribute->reference_view_name);
                $entity = $str->explode(':')->shift();
//                $items->with($entity);
            }
            return $items->paginate();
        }
        return \DB::table($referenced_table_name)
            ->select($columns)
            ->paginate();
    }

    public function updatePropertyValue($type, $property, $key, $value): void
    {
        $this->values[$type][$property][$key] = $value;
    }

    public function updateStructureCache(): void
    {
        cache()->delete('elements');
    }

    public function delete()
    {
        try {
            $this->model->delete();
            Toastr::instance($this)->success('Item removido');
            $this->redirect('/');
        } catch (Exception $exception) {
            Toastr::instance($this)->error('O Item não pôde ser removido')->dispatch();
            throw $exception;
        }
    }

    public function getKeyValue(ElementModel $element, $item, $reference_view_name)
    {
        try {
            if ($reference_view_name) {
                $str = str($reference_view_name);
                $entity = $str->explode(':')->shift();
                $prop = $str->explode(':')->pop();
                $entities = str($entity)->explode('.');
                $relation = $item;
                if ($entities->count() > 0) {
                    foreach ($entities as $entity) {
                        if (empty($relation->{$entity})) {
                            $relation->load($entity);
                            if (isset($relation->{$entity})) {
                                $relation = $relation->{$entity};
                            }
                            continue;
                        }
                        $relation = $relation->{$entity};
                    }
                }
                if (!isset($relation->{$prop})) {
                    $prop = $this->getValue($element);
                }
                $value = $relation->{$prop};
                if (!$value) {
                    $value = $item->id;
                }
            } else {
                $prop = $this->getValue($element);
                $value = $item->{$prop} ?? $item->id;
            }
            $key = $item->id ?: $item;
            return [$key, $value];
        } catch (Exception $exception) {
            throw $exception;
        }
    }

    protected function getValue($element): string
    {
        $referenced_table_name = $element->attribute->referenced_table_name;
        $table = ModuleTableModel::query()->where('name', $referenced_table_name)->first();
        $name_exists = $table->attributes()->where('name', 'name')->exists();
        if ($name_exists) {
            return 'name';
        } elseif ($table->attributes()->where('name', 'name')->exists()) {
            return 'nome';
        }
        return 'id';
    }


}
