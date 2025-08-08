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
use Modules\DBMap\Commands\DviRequestMakeCommand;
use Modules\DBMap\Domains\ModuleTableAttributeTypeEnum;
use Modules\DBMap\Models\ModuleTableModel;
use Modules\DvUi\Services\Plugins\Toastr\Toastr;
use Modules\Project\Entities\ProjectModuleEntity\ProjectModuleEntityEntityModel;
use Modules\Project\Models\ProjectEntityAttributeModel;
use Modules\Project\Models\ProjectModuleEntityDBModel;
use Modules\View\Entities\ModuleEntityPage\ModuleEntityPageEntityModel;
use Modules\View\Models\ElementModel;
use Modules\View\Models\ModuleEntityPageModel;
use Modules\View\Models\ViewPageStructureModel;

abstract class BaseComponent extends Component
{
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
        $attributes = $this->getStructure()
            ->elements()->whereNotNull('attribute_id')
            ->join('dbmap_module_table_attributes as attribute', 'attribute.id', 'attribute_id')
            ->whereHas('attribute', function (Builder $query) {
                $query->where('type', ModuleTableAttributeTypeEnum::getId(ModuleTableAttributeTypeEnum::decimal));
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
        $structure = $this->getStructure();
        $cache_key = $this->elementsCacheKey($structure);

        return cache()->rememberForever($cache_key, function () use ($structure) {
            return $structure->elements()
                ->whereNull('parent_id')
                ->with('attribute')
                ->with('allChildren.attribute')
                ->with('properties')
                ->get();
        });
    }

    public function render(): View
    {
        return view('base::livewire.base-form');
    }

    public function getRules()
    {
        $cache_key = 'model-' . $this->model['id'] . '-' . auth()->user()->id;
        $ttl = now()->addHours(3);
        return cache()->remember($cache_key, $ttl, function () {
            return (new DviRequestMakeCommand)->getRules($this->modelObject->getTable(), 'save', $this->model);
        });
    }

    public function save(): void
    {
        try {
            $fn = fn ($value) => toUS($value);
            $this->transformValues($fn);
            $this->validate();
            foreach ($this->values['dates'] as $property => $values) {
                $this->model[$property] = $values['date'] . ' ' . $values['time'];
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
        return ProjectEntityAttributeModel::query()
            ->where('entity_id', $entity_id)
            ->where('name', $property_name)
            ->get(['reference_view_name'])->first();
    }

    public function getReferencedTableData(ElementModel $element, ProjectEntityAttributeModel $projectAttribute): array|LengthAwarePaginator
    {
        if ($element->attribute->typeEnum() == ModuleTableAttributeTypeEnum::enum && $element->attribute->items) {
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
            if ($projectAttribute->reference_view_name) {
                $str = str($projectAttribute->reference_view_name);
                $entity = $str->explode(':')->shift(); // Todo check
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
        $structure = $this->getStructure();

        cache()->delete($this->elementsCacheKey($structure));
        Toastr::instance($this)->success(__('view::element.The cache was updated'));
        $this->dispatch('refresh')->self();
    }

    public function updateComponent(): void
    {
        $this->dispatch('refresh')->self();
    }

    public function delete(): void
    {
        try {
            $this->modelObject->delete();
            Toastr::instance($this)->success('Item removido');
            $this->redirect('/');
        } catch (Exception $exception) {
            Toastr::instance($this)->error('O Item não pôde ser removido');
            throw $exception;
        }
    }

    public function getKeyValue(ElementModel $element, $item, $reference_view_name): array
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
        $table = ModuleTableModel::query()->where('name', $referenced_table_name)->first();
        $name_exists = $table->attributes()->where('name', 'name')->exists();
        if ($name_exists) {
            return 'name';
        } elseif ($table->attributes()->where('name', 'name')->exists()) {
            return 'nome';
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

    abstract public function getStructure(): ViewPageStructureModel;

    abstract public function getExceptItems(): array;

}
