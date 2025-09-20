<?php

declare(strict_types=1);

namespace Modules\Base\Factories;

use App\Models\User;
use BadMethodCallException;
use Closure;
use File;
use Illuminate\Database\Eloquent\Factories\BelongsToRelationship;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;
use Mockery\Exception;
use Modules\Base\Contracts\BaseModel;
use Modules\Base\Contracts\BaseModelInterface;
use Modules\Base\Entities\BaseEntityModel;
use Modules\Person\Models\PersonModel;
use Modules\Person\Models\UserTypeModel;
use Modules\Project\Enums\ModuleEntityAttributeTypeEnum;
use Modules\View\Domains\ViewStructureComponentType;
use Nwidart\Modules\Facades\Module;

abstract class BaseFactory extends Factory
{
    public $for;

    public $factory;

    public static function getFakeDataViaViewStructureElementType(ViewStructureComponentType $type, $length, int|string $key, $value_default = null, $num_scale = null, $num_precision = null)
    {
        return match ($type) {
            ModuleEntityAttributeTypeEnum::datetime => now()->toDateTimeLocalString(),
            ModuleEntityAttributeTypeEnum::date => now()->toDateString(),
            ModuleEntityAttributeTypeEnum::time => now()->toTimeString(),
            ModuleEntityAttributeTypeEnum::text => $value_default ?? fake()->sentence(),
            ModuleEntityAttributeTypeEnum::varchar => $value_default ?? self::getFakeValue($key, $length),
            ModuleEntityAttributeTypeEnum::boolean => $value_default ?? fake()->boolean(),
            ModuleEntityAttributeTypeEnum::decimal => $value_default ?? fake()->randomFloat($num_scale, 1, str_pad('9', $num_precision - $num_scale, '9')),
            ModuleEntityAttributeTypeEnum::float => $value_default ?? fake()->randomFloat(2, 1, 999999),
            ModuleEntityAttributeTypeEnum::smallint, ModuleEntityAttributeTypeEnum::int, ModuleEntityAttributeTypeEnum::bigint => $value_default ?? fake()->numberBetween(1, 90),
            default => 1
        };
    }

    public static function getFakeValue(int|string $key, $length)
    {
        if ($key === 'name') {
            $length = (int) max($length, 10);

            $min = (int) round($length / 2);

            return fake()->text(random_int($min, $length));
        }
        if ($key === 'email') {
            return now()->timestamp.'_'.fake()->unique()->email();
        }
        if ($key === 'uuid') {
            return fake()->uuid();
        }
        if (str($key)->contains('cpf')) {
            return fake('pt_BR')->cpf();
        }
        if (str($key)->contains('cnpj')) {
            return fake('pt_BR')->cnpj();
        }
        if (str($key)->contains('rg')) {
            return fake('pt_BR')->rg();
        }
        if (str($key)->contains('path')) {
            $extension = collect(['pdf', 'png', 'jpeg', 'jpg'])->random();

            return UploadedFile::fake()->create('test.'.$extension)->store('temp_seed_files');
        }
        if (in_array($key, ['telefone', 'phone'])) {
            return '1199'.random_int(100, 999).random_int(10, 99).random_int(10, 99);
        }
        if (str($key)->contains(['url', 'link'])) {
            return str(fake()->unique()->url())->append('?'.fake()->randomNumber(3, true))->value();
        }

        return str(fake()->sentence(3))->substr(0, $length)->value();
    }

    public static function getFakeDataViaTableAttributeType(
        ViewStructureComponentType $type,
        $length,
        int|string $key,
        $value_default = null,
        $num_scale = null,
        $num_precision = null
    ) {
        return match ($type) {
            ViewStructureComponentType::datetime => now()->toDateTimeLocalString(),
            ViewStructureComponentType::date => now()->toDateString(),
            ViewStructureComponentType::time => now()->toTimeString(),
            ViewStructureComponentType::text_multiline => $value_default ?? fake()->text($length < 5 ? 10 : $length),
            ViewStructureComponentType::text => $value_default ?? self::getFakeValue($key, $length),
            ViewStructureComponentType::html => $value_default ?? self::getFakeValue($key, $length),
            ViewStructureComponentType::checkbox_unique => $value_default ?? fake()->boolean(),
            ViewStructureComponentType::decimal => $value_default ?? fake()->randomFloat($num_scale, 1, str_pad('9', $num_precision - $num_scale, '9')),
            ViewStructureComponentType::float => $value_default ?? fake()->randomFloat($num_scale, 1, str_pad('9', $num_precision - $num_scale, '9')),
            ViewStructureComponentType::number => $value_default ?? fake()->numberBetween(1, $length),
            default => 1
        };
    }

    public function createFn(Closure $fn)
    {
        /** @var BaseModel $model */
        $model = new $this->model;
        $entity_class = $model->modelEntity();
        $entity = new $entity_class;

        return parent::create($fn($entity->props()));
    }

    /**@param Factory|Model $factory */
    public function for($factory, $relationship = null)
    {
        if (empty($factory)) {
            $called_class = get_called_class();
            Log::error("factory $factory - $relationship not found in: $called_class");

            return null;
        }
        $related = $factory;
        if ($factory instanceof Factory) {
            $related = $factory->modelName();
        } elseif ($factory instanceof Model) {
            $related = class_basename($factory);
        }

        if (! $related) {
            $called_class = get_called_class();
            throw new \Exception("factory $factory - $relationship not found in: $called_class");
        }
        $relationship = $relationship ?? $this->guessRelationship($related);

        return $this->newInstance([
            'for' => $this->for->concat([new class($factory, $relationship) extends BelongsToRelationship
            {
                public $factory;
            }]),
        ]);
    }

    public function definition()
    {
        return $this->getValues();
    }

    protected function guessRelationship(string $related)
    {
        $available_methods = $this->availableMethods($related);
        foreach ($available_methods as $possibility) {
            if (method_exists($this->modelName(), $possibility)) {
                return $possibility;
            }
        }

        return parent::guessRelationship($related);
    }

    protected function availableMethods(string $related): array
    {
        $class_basename = class_basename($related);
        $singular = Str::camel($class_basename);
        $plural = Str::plural($singular);
        $collection = str($class_basename)->snake()->explode('_');

        $possibilities[] = $plural;
        $possibilities[] = $singular;

        $collection->pop();
        $pop_singular = Str::camel($collection->join('_'));
        $possibilities[] = Str::plural($pop_singular);
        $possibilities[] = $pop_singular;

        $collection->shift();
        $shift_singular = Str::camel($collection->join(''));
        $possibilities[] = Str::plural($shift_singular);
        $possibilities[] = $shift_singular;

        foreach ($collection as $item) {
            $camel = Str::camel($item);
            $possibilities[] = Str::plural($camel);
            $possibilities[] = $camel;
        }

        return $possibilities;
    }

    protected function getValues($fixed_values = []): array
    {
        try {

            $columns = $this->getFkColumns($fixed_values);

            $another_columns = $this->defineValuesForOptionalFields($columns, $fixed_values);

            $merge = array_merge($columns, $another_columns);
            $columns = [];
            foreach ($merge as $key => $item) {
                if (! collect($item)->keys()->contains('value')) {
                    continue;
                }
                $columns[$key] = $item['value'];
            }

            return $columns;
        } catch (Exception $e) {
            throw new Exception('Model: '.$this->model.' - '.$e->getMessage());
        }
    }

    protected function getFkColumns($fixed_values): array
    {
        $model = $this->model;
        $entity = (new $model)->modelEntity()::props();

        $columns = $this->getTableColumns($entity, $fixed_values);
        $table_models = $this->getTableModels();
        $indexes = $this->getTableIndexes($entity);
        $fks = $this->getTableForeignKeys($entity);
        foreach ($fks as $fk) {
            $column = $fk['columns'][0];

            $columns[$column]['fk'] = true;

            if (! empty($columns[$column]['value'])) {
                continue;
            }

            if ($this->stateContainColumn($column, $columns)) {
                continue;
            }
            $foreignTableName = $fk['foreign_table'];
            $fk_model_class = $table_models[$foreignTableName];
            $has_for = false;
            $this->for->each(function ($i) use (&$has_for, $fk_model_class, &$columns, $column): void {
                if (is_a($i->factory, $fk_model_class)) {
                    $has_for = true;
                    $columns[$column]['value'] = $i->factory->id;
                }
            });
            if ($has_for) {
                continue;
            }
            if (! $this->columnsContain($columns, $column)) {
                continue;
            }

            // Verifica se é a mesma tabela
            // Se houver chave estrangeira para msm tabela irá causar um loop infinito. (é comum em campos como parent_id)
            if ($foreignTableName === $entity->table) {
                /** @var BaseModel $model */
                $columns[$column]['value'] = $model::query()->first()->id ?? null;

                continue;
            }
            $this->validate($table_models, $foreignTableName, $entity, $fk);

            if ($this->modelIsEmpty($model)) {
                $columns[$column]['value'] = $this->createRelation($fk_model_class);

                continue;
            }

            $contain_in_index_unique = $this->containInIndexUnique($indexes, $column);

            if (! $contain_in_index_unique) {
                $columns[$column]['value'] = $this->randomOrNewRelation($fk_model_class, $model, $column);

                continue;
            }

            if (! $this->indexIsUniqueMultiple($indexes, $column)) {
                $columns[$column]['value'] = $this->createRelation($fk_model_class);

                continue;

            }

            // Todo get all unique columns and check if has in $columns with same values
            // ...

            $fk_id = $this->randomRelationId($fk_model_class, $model, $column);

            if ($fk_id) {
                $columns[$column]['value'] = $fk_id;

                continue;
            }

            $columns[$column]['value'] = $this->createRelation($fk_model_class);
        }

        return $columns;
    }

    protected function getTableColumns(BaseEntityModel $entity, mixed $fixed_values): array
    {
        return cache()->rememberForever('table_columns_'.$entity->table, function () use ($entity, $fixed_values) {
            $columns = [];

            $tableColumns = Schema::getColumns($entity->table);

            foreach ($tableColumns as $column) {
                $column_name = $column['name'];
                if ($column_name === 'id') {
                    continue;
                }

                $columns[$column_name]['obj'] = $column;
                $columns[$column_name]['value'] = $fixed_values[$column_name] ?? null;
                $columns[$column_name]['required'] = ! $column['nullable'];
                $columns[$column_name]['fk'] = null;
            }

            return $columns;
        });
    }

    protected function getTableModels(): array
    {
        $fn = function () {
            $modules = Module::allEnabled();
            if (count($modules) === 0) {
                throw new \Exception(trans('base::default.Enable any module'));
            }
            $table_model = [];
            /** @var \Nwidart\Modules\Laravel\Module $module */
            foreach ($modules as $module) {
                if (! is_dir(module_path($module->getName(), 'app/Models'))) {
                    if (! is_dir(module_path($module->getName(), 'Models'))) {
                        continue;
                    }
                }
                $files = File::files(module_path($module->getName(), 'app/Models'));
                foreach ($files as $file) {
                    $model = 'Modules/'.$module->getName().'/Models/'.$file->getFilenameWithoutExtension();
                    /** @var BaseModel $model */
                    $model = str($model)->replace('/', '\\')->value();

                    if (in_array(BaseModelInterface::class, class_implements($model))) {
                        $table_model[$model::table()] = $model;
                    }
                }
            }

            if (! isset($table_model['users'])) {
                $table_model['users'] = User::class;
            }

            return $table_model;
        };

        return $fn();
    }

    protected function stateContainColumn(string $column, array $columns): bool
    {
        return $this->states->contains(function ($i, $v) use ($column, &$columns) {
            if (is_a($i, Closure::class)) {
                $result = $i();
                if ($value = $result[$column] ?? null) {
                    $columns[$column]['value'] = $value;

                    return true;
                }
            }
            if (is_a($i, Sequence::class)) {
                /** @var Sequence $i */
                $all = collect($i)->all();

                return collect($all)->contains(function ($k, $v) use ($column, &$columns, $i) {
                    return collect($k)->contains(function ($v2, $key) use ($column, $i) {
                        if (is_a($v2, Closure::class)) {
                            $result = $v2($i);

                            return isset($result[$column]);
                        }

                        return isset($v2[$column]);
                    });
                });
            }

            return false;
        });
    }

    protected function columnsContain(array $columns, string $column): bool
    {
        return collect($columns)->contains(function ($col) use ($column) {
            if (! isset($col['obj'])) {
                return false;
            }

            return $col['obj']['name'] === $column;
        });
    }

    protected function validate(array $table_models, string $foreignTableName, BaseEntityModel $entity, array $fk): void
    {
        if (! isset($table_models[$foreignTableName]) && config('app.env') === 'local') {
            \Log::info(collect($table_models)->toJson());
        }
        if (! isset($table_models[$foreignTableName])) {
            $name = is_object($fk) ? $fk->getName() : $fk['name'];
            throw new Exception("analisar foreignTableName $entity->table. ' '. $foreignTableName em {$name} ");
        }
    }

    protected function modelIsEmpty(string $model): bool
    {
        /** @var BaseModel $model */
        return $model::query()->count() === 0;
    }

    protected function createRelation(string $model_class): int
    {
        $attributes = [];
        /** @var BaseModel $model_class */
        if ($model_class === User::class && $default = config('person.seed.user.types.default')) {
            $attributes = ['type_id' => $default];

            $person = PersonModel::factory()->create();

            $attributes['name'] = $person->name;
            $attributes['person_id'] = $person->id;
        }

        return $model_class::factory()->create($attributes)->id;
    }

    protected function containInIndexUnique(array $indexes, string $column): bool
    {
        foreach ($indexes as $index) {
            if (! $index['unique']) {
                continue;
            }
            foreach ($index['columns'] as $_column) {
                if ($_column === $column) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param  string|BaseModel  $fk_model_class
     * @param  string|BaseModel  $model_class
     */
    protected function randomOrNewRelation(string $fk_model_class, string $model_class, string $attribute_id): int
    {
        if ($fk_model_class === User::class) {
            return $this->createRelation($fk_model_class);
        }

        if ($relation_id = $this->randomRelationId($fk_model_class, $model_class, $attribute_id)) {
            return $relation_id;
        }

        return $this->createRelation($fk_model_class);
    }

    /**
     * @param  string|BaseModel  $fk_model_class
     * @param  string|BaseModel  $model_class
     */
    protected function randomRelationId(string $fk_model_class, string $model_class, string $attribute_id): ?int
    {
        if ($fk_model_class === UserTypeModel::class) {
            return config('person.seed.user.types.default');
        }

        /** @var BaseModel $fk_model_class */
        return $fk_model_class::query()->select($fk_model_class::table().'.*')
            ->leftJoin($model_class::table().' as tb1', 'tb1.'.$attribute_id, $fk_model_class::table().'.id')
            ->whereNull('tb1.'.$attribute_id)
            ->limit(1)->first()
            ->id ?? null;
    }

    protected function indexIsUniqueMultiple(array $indexes, mixed $column): bool
    {
        return collect($indexes)->contains(function ($index) use ($column) {
            if (is_array($index)) {
                $columns = $index['columns'];
            }
            if (is_object($index)) {
                $columns = $index->getColumns();
            }
            $contains = collect($columns)->contains($column);

            return isset($index['unique']) && $contains && count($columns) > 1;
        });
    }

    protected function defineValuesForOptionalFields(array $columns, mixed $fixed_values): array
    {
        $another_columns = collect($columns)->filter(fn ($c) => empty($c['value']) && empty($c['fk']))->toArray();
        foreach ($another_columns as $key => $item) {
            $obj = $item['obj'];

            if ($obj['name'] === 'deleted_at') {
                continue;
            }

            if (isset($fixed_values[$key])) {
                $another_columns[$key]['value'] = $fixed_values[$key];

                continue;
            }

            if ($obj['name'] === 'remember_token') {
                $another_columns[$key]['value'] = Str::random(10);

                continue;
            }

            $type = $obj['type_name'];

            $str = str($obj['type']);

            $length = null;
            $scale = null;
            $precision = null;
            if ($str->contains(['(', ')'])) {
                $length = $str->remove([$type, '(', ')'])->value();
                if (str($length)->contains(',')) {
                    $first = str($length)->explode(' ')->first();
                    $collection = str($first)->explode(',');
                    $precision = $collection->first();
                    $scale = $collection->last();
                }
            }

            $type = ViewStructureComponentType::fromDBType($type, $length);
            $value = self::getFakeDataViaTableAttributeType(
                type: $type,
                length: $length,
                key: $key,
                value_default: $obj['default'] === 'CURRENT_TIMESTAMP' ? now()->toDateTimeLocalString() : $obj['default'],
                num_scale: $scale,
                num_precision: $precision
            );
            $another_columns[$key]['value'] = $value;

            if ($key === 'parent_id') {
                $another_columns[$key]['value'] = null;
            }
        }

        return $another_columns;
    }

    protected function createName(): string
    {
        return $this->removeAbreviations($this->faker->name())->trim()->value();
    }

    protected function removeAbreviations(string $str): Stringable
    {
        return str($str)
            ->replace(['Dr.', 'Dra.', 'Sr.', 'Sra.', 'Srta.', 'Jr.', ' da', ' de'], '')
            ->trim();
    }

    protected function getEmail(string $name): string
    {
        return str(iconv('UTF-8', 'ASCII//TRANSLIT', $this->removeAbreviations($name)->value()))
            ->lower()
            ->explode(' ')
            ->shift(3)
            ->join('_')
            .'@gmail.com';
    }

    protected function throwBadMethod(string $related, array $available_methods)
    {
        $str = sprintf('Call to undefined method %s::%s()', static::class, $related);
        $str .= '. Available methods: '.json_encode($available_methods);
        throw new BadMethodCallException($str);
    }

    protected function totalPorTabelaDeFkUnique(array $fks, array $indexes, array $table_models): array
    {
        $amount_table_foreign_keys = [];
        foreach ($fks as $fk) {
            $fk_column = $fk->getLocalColumns()[0];
            $contain_unique = false;
            foreach ($indexes as $index) {
                if ($index->isUnique()) {
                    continue;
                }
                if ($contain_unique = collect($index->getColumns())->contains(fn ($c) => $c === $fk_column)) {
                    break;
                }
            }
            if (! $contain_unique) {
                continue;
            }
            /** @var BaseModel|string $table */
            $table = $fk->getForeignTableName();
            $class = $table_models[$table];
            $amount_table_foreign_keys[$table] = $class::query()->count();
        }

        return $amount_table_foreign_keys;
    }

    protected function relationIsEmpty(string $class): bool
    {
        /** @var BaseModel $class */
        return $class::query()->count() === 0;
    }

    protected function getTableIndexes(BaseEntityModel $entity): array
    {
        return cache()->rememberForever("table::{$entity->table}::indexes", fn () => Schema::getIndexes($entity->table));
    }

    protected function getTableForeignKeys(BaseEntityModel $entity): array
    {
        return cache()->rememberForever("table::{$entity->table}::foreign_keys", fn () => Schema::getForeignKeys($entity->table));
    }
}
