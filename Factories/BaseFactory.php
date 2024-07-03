<?php

namespace Modules\Base\Factories;

use App\Models\User;
use BadMethodCallException;
use Closure;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Illuminate\Database\Eloquent\Factories\BelongsToRelationship;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;
use Mockery\Exception;
use Modules\App\Models\UserTypeModel;
use Modules\Base\Entities\BaseEntity;
use Modules\Base\Entities\Props;
use Modules\Base\Models\BaseModel;
use Modules\DBMap\Domains\ModuleTableAttributeTypeEnum;
use Modules\View\Domains\ViewStructureComponentType;
use Nwidart\Modules\Facades\Module;

abstract class BaseFactory extends Factory
{
    public $for;
    public $factory;

    public static function getFakeDataViaViewStructureElementType(ViewStructureComponentType $type, $length, int|string $key, $value_default = null, $num_scale = null, $num_precision = null)
    {
        return match ($type) {
            ModuleTableAttributeTypeEnum::DATETIME, ModuleTableAttributeTypeEnum::DATE, ModuleTableAttributeTypeEnum::TIME => now(),
            ModuleTableAttributeTypeEnum::TEXT => $value_default ?? fake()->sentence(),
            ModuleTableAttributeTypeEnum::VARCHAR => $value_default ?? self::getFakeValue($key, $length),
            ModuleTableAttributeTypeEnum::BOOLEAN => $value_default ?? fake()->boolean(),
            ModuleTableAttributeTypeEnum::DECIMAL => $value_default ?? fake()->randomFloat($num_scale, 1, str_pad(9, $num_precision - $num_scale, 9)),
            ModuleTableAttributeTypeEnum::FLOAT => $value_default ?? fake()->randomFloat(2, 1, 999999),
            ModuleTableAttributeTypeEnum::SMALLINT, ModuleTableAttributeTypeEnum::INT, ModuleTableAttributeTypeEnum::BIGINT => $value_default ?? fake()->numberBetween(1, 90),
            default => 1
        };
    }

    public static function getFakeValue(int|string $key, $length)
    {
        if ($key == 'name') {
            return fake()->words(3, true);
        }
        if ($key == 'email') {
            return random_int(1000, 9999) . '_' . fake()->email();
        }
        if ($key == 'uuid') {
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
            return UploadedFile::fake()->create('test.' . $extension)->store('temp_seed_files');
        }
        if (in_array($key, ['telefone', 'phone'])) {
            return '1199' . random_int(100, 999) . random_int(10, 99) . random_int(10, 99);
        }
        return str(fake()->sentence(3))->substr(0, $length)->value();
    }

    public function createFn(\Closure $fn)
    {
        /**@var BaseModel $model */
        $model = new $this->model;
        $entity_class = $model->modelEntity();
        $entity = new $entity_class;
        return parent::create($fn($entity->props()));
    }

    /**@param Factory|Model $factory */
    public function for($factory, $relationship = null)
    {
        $related = $factory;
        if ($factory instanceof Factory) {
            $related = $factory->modelName();
        } elseif ($factory instanceof Model) {
            $related = class_basename($factory);
        }

        if (!$related) {
            throw new \Exception('factory not found');
        }
        $relationship = $relationship ?? $this->guessRelationship($related);

        return $this->newInstance([
            'for' => $this->for->concat([new class($factory, $relationship) extends BelongsToRelationship {
                public $factory;
            }]),
        ]);
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

    public function definition()
    {
        return $this->getValues();
    }

    protected function getValues($fixed_values = []): array
    {
        //Todo Se campos de fk constam na lista de unique, devem criar um novo.
        //preciso saber se a propriedade é uma chave estrangeira
        //se for, gerar um id a partir do modelo

        /**@var BaseModel $model */
        $model = $this->model;
        $entity = (new $model)->modelEntity()::props();

        $columns = $this->getTableColumns($entity, $fixed_values);

        //2 define valores para campos de chave estrangeira
        $table_models = $this->getTableModels();

        $indexes = Schema::getConnection()->getDoctrineSchemaManager()->listTableIndexes($entity->table);
        $fks = Schema::getConnection()->getDoctrineSchemaManager()->listTableForeignKeys($entity->table);

        foreach ($fks as $fk) {
            $column = $fk->getLocalColumns()[0];
            $columns[$column]['fk'] = true;

            if (!empty($columns[$column]['value'])) {
                continue;
            }

            if ($this->stateContainColumn($column, $columns)) {
                continue;
            }
            $foreignTableName = $fk->getForeignTableName();
            /**@var BaseModel $fk_model_class */
            $fk_model_class = $table_models[$foreignTableName];
            $has_for = $this->for->count() && is_a($this->for->first()->factory, $fk_model_class);
            if ($has_for) {
                continue;
            }
            if ($this->columnsContain($columns, $column)) {
//            }
//            if (collect($columns)->contains(function ($col) use ($column) {
//                /**@var Column $obj */
//                $obj = $col['obj'];
//                return $obj->getName() == $column;
//            })) {
                //se houver uma chave estrangeira para msm tabela o que eh comum em
                //campos ex. parent_id, irá causar um loop infinito
                //deve verificar se a tabela é a mesma
                if ($foreignTableName == $entity->table) {
                    $columns[$column]['value'] = $model::query()->first()->id ?? null;
                    continue;
                }
                $this->validate($table_models, $foreignTableName, $entity, $fk);

                if ($this->modelIsEmpty($model)) {
                    $columns[$column]['value'] = $this->createRelation($fk_model_class);
                    continue;
                }

                $contain_in_index_unique = $this->containInIndexUnique($indexes, $column);

                if (!$contain_in_index_unique) {
                    $columns[$column]['value'] = $this->randomOrNewRelation($fk_model_class, $model, $column);
                    continue;
                } elseif ($fk_model_class == User::class) {
                    $columns[$column]['value'] = $this->createRelation($fk_model_class);
                    continue;
                }

                $fk_id = $this->randomRelationId($fk_model_class, $model, $column);
                if (!$fk_id) {
                    $columns[$column]['value'] = $this->createRelation($fk_model_class);
                    continue;
                }

                $index_is_unique_multiple = collect($indexes)->contains(function ($index) use ($column) {
                    return $index->isUnique() && collect($index->getColumns())->contains($column) && count($index->getColumns()) > 1;
                });
                if ($index_is_unique_multiple) {
                    //Todo get all unique columns and check if has in $columns with same values
                    $columns[$column]['value'] = $fk_id;
                    continue;
                }

                $columns[$column]['value'] = $fk_id;
            }
        }

        $another_columns = $this->defineValuesForOptionalFields($columns, $fixed_values);

        $merge = array_merge($columns, $another_columns);
        $columns = [];
        foreach ($merge as $key => $item) {
            $columns[$key] = $item['value'];
        }
        return $columns;
    }

    protected function createRelation(string $model_class): int
    {
        $attributes = [];
        /**@var BaseModel $model_class */
        if ($model_class == User::class && $defult = config('app.seed.user.types.default')) {
            $attributes = ['type_id' => $defult];
        }
        return $model_class::factory()->create($attributes)->id;
    }

    /**
     * @return array
     * @throws \ReflectionException
     */
    protected function getTableModels(): array
    {
        $fn = function () {
            $modules = Module::allEnabled();
            if (count($modules) == 0) {
                throw new \Exception('Não tem módulos habilitados');
            }
            $table_model = [];
            /**@var \Nwidart\Modules\Laravel\Module $module */
            foreach ($modules as $module) {
                if ($module->getName() == 'Base') {
                    continue;
                }
                $module_model_path = 'Modules/' . $module->getName() . '/Models';
                if (is_dir(base_path($module_model_path))) {
                    $files = \File::files(base_path($module_model_path));
                    foreach ($files as $file) {
                        /*if ($file->getExtension() !== '.php') {
                            continue;
                        }*/
                        /**@var BaseModel $model_f */
                        $model_f = str($module_model_path . '/' . $file->getFilenameWithoutExtension())->replace('/', '\\')->value();
                        $reflectionClass = new \ReflectionClass($model_f);
                        if ($reflectionClass->isSubclassOf(BaseModel::class)) {
                            $table_model[$model_f::table()] = $model_f;
                        }
                    }
                }
            }

            if (!isset($table_model['users'])) {
                $table_model['users'] = User::class;
            }
            return $table_model;
        };
        return $fn();
//        return cache()->rememberForever('dvi_table_models', $fn);
    }

    public static function getFakeDataViaTableAttributeType(ViewStructureComponentType $type, $length, int|string $key, $value_default = null, $num_scale = null, $num_precision = null)
    {
        return match ($type) {
            ViewStructureComponentType::datetime, ViewStructureComponentType::date, ViewStructureComponentType::time => now(),
            ViewStructureComponentType::text_multiline => $value_default ?? fake()->sentence(),
            ViewStructureComponentType::text => $value_default ?? self::getFakeValue($key, $length),
            ViewStructureComponentType::checkbox_unique => $value_default ?? fake()->boolean(),
            ViewStructureComponentType::decimal => $value_default ?? fake()->randomFloat($num_scale, 1, str_pad(9, $num_precision - $num_scale, 9)),
            ViewStructureComponentType::float => $value_default ?? fake()->randomFloat(2, 1, 999999),
            ViewStructureComponentType::number => $value_default ?? fake()->numberBetween(1, 90),
            default => 1
        };
    }

    protected function createName(): string
    {
        return $this->removeAbreviations($this->faker->name())->trim()->value();
    }

    protected function removeAbreviations(string $str): Stringable
    {
        return str($str)
            ->replace(['Dr.', 'Dra.', 'Sr.', 'Sra.', 'Srta.', 'Jr.', ' da', ' de'], '')->trim();
    }

    protected function getEmail(string $name): string
    {
        return str(iconv('UTF-8', 'ASCII//TRANSLIT', $this->removeAbreviations($name)))
                ->lower()->explode(' ')->shift(3)->join('_') . '@gmail.com';
    }

    protected function throwBadMethod(string $related, array $available_methods)
    {
        $str = sprintf('Call to undefined method %s::%s()', static::class, $related);
        $str .= ". Available methods: " . json_encode($available_methods);
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
                if ($contain_unique = collect($index->getColumns())->contains(fn($c) => $c == $fk_column)) {
                    break;
                }
            }
            if (!$contain_unique) {
                continue;
            }
            /**@var BaseModel|string $table */
            $table = $fk->getForeignTableName();
            $class = $table_models[$table];
            $amount_table_foreign_keys[$table] = $class::query()->count();
        }
        return $amount_table_foreign_keys;
    }

    protected function defineValuesForOptionalFields(array $columns, mixed $fixed_values): array
    {
        $another_columns = collect($columns)->filter(fn($c) => empty($c['value']) && empty($c['fk']))->toArray();
        foreach ($another_columns as $key => $item) {
            /**@var Column $obj */
            $obj = $item['obj'];

            if ($obj->getName() == 'deleted_at') {
                continue;
            }
            if (!$item['required'] && !$this->faker->boolean()) {
                continue;
            }
            if (isset($fixed_values[$key])) {
                $another_columns[$key]['value'] = $fixed_values[$key];
                continue;
            }

            if ($obj->getName() == 'remember_token') {
                $another_columns[$key]['value'] = Str::random(10);
                continue;
            }

            $type = (new \ReflectionObject($obj->getType()))->getName();
            $another_columns[$key]['value'] = self::getFakeDataViaTableAttributeType(
                ViewStructureComponentType::fromDBType($type),
                $obj->getLength(),
                $key,
                $obj->getDefault(),
                $obj->getScale(),
                $obj->getPrecision()
            );

            if ($key == 'parent_id') {
                $another_columns[$key]['value'] = null;
            }
        }
        return $another_columns;
    }

    protected function relationIsEmpty(string $class): bool
    {
        /**@var BaseModel $class */
        return $class::query()->count() == 0;
    }

    /**
     * @param string|BaseModel $fk_model_class
     * @param string|BaseModel $model_class
     * @param string $attribute_id
     * @return int|null
     */
    protected function randomRelationId(string $fk_model_class, string $model_class, string $attribute_id): int|null
    {
        if ($fk_model_class == UserTypeModel::class) {
            return config('app.seed.user.types.default');
        }
        $already_used_ids = $model_class::query()->pluck($attribute_id)->all();

        /**@var BaseModel $fk_model_class */
        return $fk_model_class::query()->whereNotIn('id', $already_used_ids)->inRandomOrder()->first()->id ?? null;
    }

    /**
     * @param string|BaseModel $fk_model_class
     * @param string|BaseModel $model_class
     * @param string $attribute_id
     * @return int
     */
    protected function randomOrNewRelation(string $fk_model_class, string $model_class, string $attribute_id): int
    {
        if ($fk_model_class == User::class) {
            return $this->createRelation($fk_model_class);
        }

        return $this->randomRelationId($fk_model_class, $model_class, $attribute_id)
            ?: $this->createRelation($fk_model_class);
    }

    protected function modelIsEmpty(string $model): bool
    {
        /**@var BaseModel $model */
        return $model::query()->count() == 0;
    }

    protected function containInIndexUnique(array $indexes, string $column): bool
    {
        $contain_in_index_unique = false;

        foreach ($indexes as $index) {
            if (!$index->isUnique()) {
                continue;
            }
            foreach ($index->getColumns() as $_column) {
                if ($_column == $column) {
                    $contain_in_index_unique = true;
                }
            }
            /*foreach ($index->getColumns() as $_column) {
                if($contain_in_index_unique = ($_column == $column)) {
                    break;
                }
            }*/
//                    $contain_in_index_unique = collect($index->getColumns())->contains(fn($c) => $c == $column);
        }
        return $contain_in_index_unique;
    }

    protected function columnsContain(array $columns, string $column): bool
    {
        return collect($columns)->contains(function ($col) use ($column) {
            /**@var Column $obj */
            $obj = $col['obj'];
            return $obj->getName() == $column;
        });
    }

    /**
     * @param Props|object $entity
     * @param array $columns
     * @param mixed $fixed_values
     * @return array
     * @throws \Doctrine\DBAL\Exception
     */
    protected function getTableColumns($entity, mixed $fixed_values): array
    {
        $columns = [];
        $tableColumns = \Schema::getConnection()
            ->getDoctrineSchemaManager()
            ->listTableColumns($entity->table);

        foreach ($tableColumns as $column) {
            $column_name = $column->getName();
            if ($column_name == 'id') {
                continue;
            }

            $columns[$column_name]['obj'] = $column;
            $columns[$column_name]['value'] = $fixed_values[$column_name] ?? null;
            $columns[$column_name]['required'] = $column->getNotnull();
            $columns[$column_name]['fk'] = null;
        }
        return $columns;
    }

    protected function stateContainColumn(string $column, array $columns): bool
    {
        return $this->states->contains(function ($i, $v) use ($column, &$columns) {
            if (is_a($i, \Closure::class)) {
                $result = $i();
                if ($value = $result[$column] ?? null) {
                    $columns[$column]['value'] = $value;
                    return true;
                }
            }
            if (is_a($i, Sequence::class)) {
                /**@var Sequence $i */
                $all = collect($i)->all();
                return collect($all)->contains(function ($k, $v) use ($column, &$columns, $i) {
                    return collect($k)->contains(function ($v2, $key) use ($column, $i, $k) {
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

    protected function validate(array $table_models, string $foreignTableName, BaseEntity $entity, ForeignKeyConstraint $fk)
    {
        if (!isset($table_models[$foreignTableName]) && config('app.env') == 'local') {
            \Log::info(collect($table_models)->toJson());
        }
        if (!isset($table_models[$foreignTableName])) {
            throw new Exception("analisar foreignTableName $entity->table. ' '. $foreignTableName em {$fk->getName()} ");
        }
    }
}
