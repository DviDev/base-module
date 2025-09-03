<?php

namespace Modules\Base\Livewire;

use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Modules\Base\Contracts\BaseModel;
use Modules\DBMap\Traits\DynamicRules;
use Modules\DvUi\Services\Plugins\Toastr\Toastr;
use Modules\Project\Entities\ProjectModuleEntity\ProjectModuleEntityEntityModel;
use Modules\Project\Enums\ModuleEntityAttributeTypeEnum;
use Modules\Project\Models\ProjectModuleEntityAttributeModel;
use Modules\Project\Models\ProjectModuleEntityDBModel;
use Modules\View\Entities\ModuleEntityPage\ModuleEntityPageEntityModel;
use Modules\View\Models\ElementModel;
use Modules\View\Models\ModuleEntityPageModel;
use Modules\View\Models\ViewPageStructureModel;

abstract class BaseLivewireComponent extends Component
{
    use DynamicRules;

    public $redirect_after_save = true;

    #[Locked]
    public $modelObject;

    public $model;

    public array $values = [];

    public ?ModuleEntityPageModel $page;

    protected $visible_rows;

    protected $listeners = ['refresh' => '$refresh'];

    public function mount(): void
    {
        $this->modelObject = $this->model;
        $this->model = $this->model->attributesToArray();
        $this->setPage();
        if (! $this->page) {
            return;
        }
        $fn = fn ($value) => toBRL($value);
        $this->transformValues($fn);
        $this->values['dates'] = [];
        foreach ($this->model as $attribute => $value) {
            $prop = $this->model[$attribute];
            if (is_a($prop, Carbon::class)) {
                /** @var Carbon $value */
                $value = $prop;
                $this->values['dates'][$attribute] = ['date' => $value->format('Y-m-d'), 'time' => $value->format('H:i')];
            }
        }
    }

    protected function transformValues($fn): void
    {
        $attributes = $this->getStructureCache()
            ->elements()->whereNotNull('attribute_id')
            ->join('dbmap_module_table_attributes as attribute', 'attribute.id', 'attribute_id')
            ->whereHas('attribute', function (Builder $query) {
                $query->where('type', ModuleEntityAttributeTypeEnum::getId(ModuleEntityAttributeTypeEnum::decimal));
            })
            ->pluck('attribute.name')->all();
        foreach ($attributes as $attribute) {
            if (empty($this->model[$attribute])) {
                continue;
            }
            $this->model[$attribute] = $fn($this->model[$attribute]);
        }
    }

    public function elements(): Collection|array
    {
        if (! $this->page) {
            Toastr::instance($this)->error(__('view::element.This page does not have a structure'));

            return [];
        }
        /** @var ViewPageStructureModel $structure */
        $structure = $this->getStructureCache();
        $cache_key = $this->elementsCacheKey($structure);

        /**
         * @return \Illuminate\Database\Eloquent\Collection
         */
        $callback = function () use ($structure) {
            return $structure->elements()
                ->whereNull('parent_id')
                ->with('attribute')
                ->with('allChildren.attribute')
                ->with('properties')
                ->get();
        };

        return cache()->rememberForever($cache_key, $callback);
    }

    abstract public function render(): View;

    public function getRules()
    {
        $cache_key = 'model-'.($this->model['id'] ?? '').'-'.auth()->user()->id;
        $ttl = now()->addHours(3);

        return cache()->remember($cache_key, $ttl, function () {
            return $this->getDynamicRules($this->modelObject->getTable(), 'save', $this->model);
        });
    }

    protected function validationAttributes(): array
    {
        $cache_key = 'validationAttributes::page-'.($this->page->id);
        $ttl = now()->addHours(3);

        return cache()->remember($cache_key, $ttl, function () {
            return $this->dynamicValidationAttributes('save');
        });
    }

    public function save(): void
    {
        try {
            $fn = fn ($value) => toUS($value);
            $this->transformValues($fn);
            $this->validate();
            foreach ($this->values['dates'] as $property => $values) {
                $this->model[$property] = $values['date'].' '.$values['time'];
            }
            $properties = $this->model;
            foreach ($properties as $property => $values) {
                $trim = trim($values);
                $this->model[$property] = empty($trim) ? null : $trim;
            }
            $this->modelObject->fill($this->model);
            $this->modelObject->save();
            if ($this->modelObject->wasRecentlyCreated) {
                session()->flash('success', str(__('the data has been saved'))->ucfirst());
                session()->flash('only_toastr');

                if (! $this->redirect_after_save) {
                    return;
                }
                $route = route($this->page->route, $this->model['id']);
                $this->redirect($route, navigate: true);

                return;
            }
            $fn = fn ($value) => toBRL($value);
            $this->transformValues($fn);
            Toastr::instance($this)->success(str(__('the data has been saved'))->ucfirst());
        } catch (ValidationException $exception) {
            $fn = fn ($value) => toBRL($value);
            $this->transformValues($fn);
            Toastr::instance($this)->error($exception->getMessage());
            throw $exception;
        } catch (Exception $exception) {
            if (config('app.env') == 'local') {
                throw $exception;
            }
            Toastr::instance($this)->error('Não foi possível salvar o item');
        }
    }

    public function projectAttribute($property_name, $entity_id)
    {
        return ProjectModuleEntityAttributeModel::query()
            ->where('entity_id', $entity_id)
            ->where('name', $property_name)
            ->get(['referenced_table_name'])->first();
    }

    public function getReferencedTableData(ElementModel $element, ProjectModuleEntityAttributeModel $projectAttribute): array|LengthAwarePaginator
    {
        if ($element->attribute->typeEnum() == ModuleEntityAttributeTypeEnum::enum && $element->attribute->items) {
            return $element->attribute->items->pluck('name')->all();
        }
        $columns = ['id'];

        $referenced_table_name = $element->structure->page->entity->name;
        $entity = ProjectModuleEntityDBModel::query()->where('name', $referenced_table_name)->first();
        $name_exists = $entity->entityAttributes()->where('name', 'name')->exists();
        $nome_exists = $entity->entityAttributes()->where('name', 'nome')->exists();
        if ($name_exists) {
            $columns[] = 'name as value';
        } elseif ($nome_exists) {
            $columns[] = 'nome as value';
        } else {
            $columns[] = 'id as value';
        }

        $entity_name = str($element->structure->page->entity->title);
        $module = $entity_name->explode(' ')->first();
        $entity_name = $entity_name->explode(' ')->filter(fn ($i) => $i !== $module)->join('\\');
        $entity_name = str($entity_name)->singular()->value();
        /** @var BaseModel $model_class */
        $model_class = "Modules\\$module\\Models\\$entity_name".'Model';
        if (class_exists($model_class)) {
            $items = $model_class::query();
            if ($projectAttribute->referenced_table_name) {
                $str = str($projectAttribute->referenced_table_name);
                $entity = $str->explode(':')->shift(); // Todo check
                // $items->with($entity);
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
        $structure = $this->getStructure();

        cache()->delete($this->elementsCacheKey($structure));
        Toastr::instance($this)->success(__('view::element.The cache was updated'));
        $this->dispatch('refresh')->self();
    }

    public function updateComponent(): void
    {
        $this->dispatch('refresh')->self();
    }

    public function delete($id): void
    {
        try {
            $this->modelObject::query()->where('id', $id)->delete();
            Toastr::instance($this)->success('Item removido');
            $this->dispatch('refresh');
        } catch (Exception $exception) {
            Toastr::instance($this)->error('O Item não pôde ser removido');
            throw $exception;
        }
    }

    public function getKeyValue(ElementModel $element, $item, $reference_table_name): array
    {
        try {
            if ($reference_table_name) {
                $str = str($reference_table_name);
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
                if (! isset($relation->{$prop})) {
                    $prop = $this->getValue($element);
                }
                $value = $relation->{$prop};
                if (! $value) {
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
        $entity = ProjectModuleEntityDBModel::query()->firstWhere('name', $referenced_table_name);
        $name_exists = $entity->entityAttributes()->where('name', 'name')->exists();
        if ($name_exists) {
            return 'name';
        }

        return 'id';
    }

    protected function elementsCacheKey(ViewPageStructureModel $structure): string
    {
        return 'structure.'.$structure->id.'.allChildren.elements';
    }

    protected function setPage(): void
    {
        if (\Request::routeIs('view.entity.page.form')) {
            if (! $this->model) {
                throw new Exception(__('Model not found'));
            }
            $this->page = $this->model;

            return;
        }

        $page = ModuleEntityPageEntityModel::props('page');
        $entity = ProjectModuleEntityEntityModel::props('entity');

        $this->page = ModuleEntityPageModel::query()
            ->select($page->table_alias.'.*')
            ->from($page->table())
            ->join($entity->table(), $entity->id, $page->entity_id)
            ->where($entity->name, $this->modelObject->getTable())
            ->where($page->route, 'like', '%.form')
            ->first();
    }

    public function getStructureCache(): ViewPageStructureModel
    {
        $key = 'page::'.$this->page->id.'::structure';

        return cache()->rememberForever($key, fn () => $this->getStructure());
    }

    abstract public function getStructure(): ViewPageStructureModel;

    abstract public function getExceptItems(): array;
}
