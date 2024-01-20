<?php

namespace Modules\Base\Http\Livewire;

use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Modules\Base\Models\BaseModel;
use Modules\DBMap\Commands\DviRequestMakeCommand;
use Modules\DBMap\Domains\ModuleTableAttributeTypeEnum;
use Modules\DBMap\Models\ModuleTableModel;
use Modules\DvUi\Services\Plugins\Toastr\Toastr;
use Modules\Seguro\Models\ApoliceModel;
use Modules\Seguro\Models\PropostaModel;
use Modules\View\Models\ElementModel;
use Modules\View\Models\ModuleEntityPageModel;
use Modules\View\Models\ViewPageStructureModel;
use phpDocumentor\Reflection\Types\Callable_;

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

    public function render()
    {
        return view('base::livewire.base-form');
    }

    /**@return ElementModel[]|Collection */
    public function elements(): Collection|array
    {
        /**@var ViewPageStructureModel $structure */
        $structure = $this->page->structures()->whereNotNull('active')->first();

        $cache_key = 'structure.' . $structure->id . '.elements';
        return cache()->rememberForever($cache_key, function () use ($structure) {
            $elements = $structure->elements()->with('allChildren')->get()->filter(function (ElementModel $e) {
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
        });
    }

    public function getRules()
    {
        $cache_key = 'model-' . $this->model->id . '-' . auth()->user()->id;

        $ttl = now()->addMinutes(30);
        return cache()->remember($cache_key, $ttl, function () {
             return (new DviRequestMakeCommand)->getRules($this->model->getTable(), 'save', $this->model);
        });
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

    public function getReferencedTableData(ElementModel $element): Builder|array
    {
        if ($element->attribute->typeEnum() == ModuleTableAttributeTypeEnum::ENUM && $element->attribute->items) {
            return str($element->attribute->items)->explode(',')->all();
        }
        $columns = ['id'];
        $referenced_table_name = $element->attribute->referenced_table_name;
        $table = ModuleTableModel::query()->where('name', $referenced_table_name)->first();
        $exists = $table->attributes()->where('name', 'name')->exists();
        $columns[] = $exists
            ? 'name as value'
            : 'id as value';

        $entity_name = str($table->entityObj->title);
        $module = $entity_name->explode(' ')->first();
        $entity_name = $entity_name->explode(' ')->filter(fn($i) => $i !== $module)->join('\\');
        $entity_name = str($entity_name)->singular()->value();
        $model_class = "Modules\\$module\\Models\\$entity_name".'Model';
        if (class_exists($model_class)) {
            /**@var BaseModel $model_class*/
            return $model_class::query()->select($columns);
        }
        return \DB::table($referenced_table_name)
            ->get($columns)
            ->all();
    }

    public function updatePropertyValue($type, $property, $key, $value): void
    {
        $this->values[$type][$property][$key] = $value;
    }

    public function updateStructureCache(): void
    {
        cache()->delete('elements');
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
}
