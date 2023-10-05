<?php

namespace Modules\Base\Factories;

use App\Models\User;
use BadMethodCallException;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\BigIntType;
use Doctrine\DBAL\Types\BooleanType;
use Doctrine\DBAL\Types\DateTimeType;
use Doctrine\DBAL\Types\DateType;
use Doctrine\DBAL\Types\DecimalType;
use Doctrine\DBAL\Types\FloatType;
use Doctrine\DBAL\Types\IntegerType;
use Doctrine\DBAL\Types\SmallIntType;
use Doctrine\DBAL\Types\StringType;
use Doctrine\DBAL\Types\TextType;
use Doctrine\DBAL\Types\TimeType;
use Illuminate\Database\Eloquent\Factories\BelongsToRelationship;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;
use Mockery\Exception;
use Modules\Base\Models\BaseModel;
use Nwidart\Modules\Facades\Module;

abstract class BaseFactory extends Factory
{
    public function createFn(\Closure $fn)
    {
        /**@var BaseModel $model */
        $model = new $this->model;
        $entity_class = $model->modelEntity();
        $entity = new $entity_class;
        return parent::create($fn($entity->props()));
    }

    public function for($factory, $relationship = null)
    {
        $related = $factory;
        if ($factory instanceof Factory) {
            $related = $factory->modelName();
        }
        if ($factory instanceof Model) {
            $related = class_basename($factory);
        }

        $relationship = $relationship ?? $this->guessRelationship($related);

        return $this->newInstance([
            'for' => $this->for->concat([new BelongsToRelationship($factory, $relationship)])
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

    protected function createName(): string
    {
        return $this->removeAbbreviations($this->faker->name())->trim()->value();
    }

    protected function removeAbbreviations(string $str): Stringable
    {
        return str($str)
            ->replace(['Dr.', 'Dra.', 'Sr.', 'Sra.', 'Srta.', 'Jr.', ' da', ' de'], '')->trim();
    }

    protected function getEmail(string $name): string
    {
        return str(iconv('UTF-8', 'ASCII//TRANSLIT', $this->removeAbbreviations($name)))
                ->lower()->explode(' ')->shift(3)->join('_') . '@gmail.com';
    }

    protected function getValues($fixed_values = []): array
    {
        //Todo Se campos de kf constam na lista de unique, devem criar um novo. (incluir esta logica dentro de getValues)

        //preciso saber se a propriedade é uma chave estrangeira
        //se for, gerar um id a partir do modelo
        /**@var BaseModel $model */
        $model = $this->model;
        $entity = (new $model)->modelEntity()::props();
        //1 define os campos obrigatorios
        $tableColumns = \Schema::getConnection()
            ->getDoctrineSchemaManager()
            ->listTableColumns($entity->table);

        $columns = [];
        foreach ($tableColumns as $column) {
            $column_name = $column->getName();
            if ($column_name == 'id') {
                continue;
            }

            $columns[$column_name]['obj'] = $column;
            $columns[$column_name]['value'] = null;
            $columns[$column_name]['required'] = $column->getNotnull();
        }

        //2 define valores para campos de chave estrangeira
        $table_models = $this->getTableModels();

        $indexes = Schema::getConnection()->getDoctrineSchemaManager()->listTableIndexes($entity->table);
        $fks = Schema::getConnection()->getDoctrineSchemaManager()->listTableForeignKeys($entity->table);

        foreach ($fks as $fk) {
            $column = $fk->getLocalColumns()[0];

            if (collect($columns)->contains(function ($col) use ($column) {
                /**@var Column $obj */
                $obj = $col['obj'];
                return $obj->getName() == $column;
            })) {
                //se houver uma chave estrangeira para msm tabela o que eh comum em
                //campos ex. parent_id, irá causar um loop infinito
                //deve verificar se a tabela é a mesma
                $foreignTableName = $fk->getForeignTableName();
                if ($foreignTableName == $entity->table) {
                    $columns[$column]['value'] = $model::query()->first()->id ?? null;
                    continue;
                }
                if (!isset($table_models[$foreignTableName]) && config('app.env') == 'local') {
                    ds($table_models);
                }
                if (!isset($table_models[$foreignTableName])) {
                    throw new Exception("analisar foreignTableName $entity->table. ' '. $foreignTableName em {$fk->getName()} ");
                }
                /**@var BaseModel $model */
                $model = $table_models[$foreignTableName];

                //check if index contain in unique columns
                $contain_in_index_unique = false;
                foreach($indexes as $i) {
                    if (!$i->isUnique()) {
                        continue;
                    }
                    foreach ($i->getColumns() as $_column) {
                        if ($_column == $column) {
                            $contain_in_index_unique = true;
                        }
                    }
                }

                $columns[$column]['value'] = !$contain_in_index_unique
                    ? ($model::query()->inRandomOrder()->first()->id ?? $model::factory()->create()->id)
                    : $model::factory()->create()->id;
            }
        }

        //define valores para os campos opcionais
        $another_columns = collect($columns)->filter(fn($c) => empty($c['value']))->toArray();
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

            $type = (new \ReflectionObject($obj->getType()))->getName();
            $another_columns[$key]['value'] = match ($type) {
                DateTimeType::class, DateType::class, TimeType::class => now(),
                TextType::class => $this->faker->sentence(),
                StringType::class => $this->getFakeValue($key, $obj),
                BooleanType::class => $obj->getDefault() ?? $this->faker->boolean(),
                DecimalType::class => $this->faker->randomFloat($obj->getScale(), 1, str_pad(9, $obj->getPrecision()-$obj->getScale(), 9)),
                FloatType::class => $this->faker->randomFloat(2, 1, 999999),
                SmallIntType::class, IntegerType::class, BigIntType::class => $this->faker->numberBetween(1, 90),
                default => 1
            };

            if ($key == 'parent_id') {
                $another_columns[$key]['value'] = null;
            }
        }

        $merge = array_merge($columns, $another_columns);
        $columns = [];
        foreach ($merge as $key => $item) {
            $columns[$key] = $item['value'];
        }
        return $columns;
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

                    $files = \File::files($module_model_path);
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

    protected function throwBadMethod(string $related, array $available_methods)
    {
        $str = sprintf('Call to undefined method %s::%s()', static::class, $related);
        $str .= ". Available methods: " . json_encode($available_methods);

        throw new BadMethodCallException($str);
    }

    public function definition()
    {
        try {
            return $this->getValues();
        } catch (\Exception $exception) {
            $str = 'Não foi possível resolver a Fabrica do modelo '. $this->model;
            if (config('app.env') == 'local') {
                $str .= ' Erro: '.$exception->getMessage().$exception->getFile().':'.$exception->getLine();
            }
            throw new \Exception($str );
        }
    }

    protected function getFakeValue(int|string $key, Column $obj)
    {
        if ($key == 'name') {
            return $this->faker->words(3, true);
        }
        if ($key == 'uuid') {
            return $this->faker->uuid();
        }
        if (in_array($key, ['telefone', 'phone'])) {
            return '1199'.random_int(100,999).random_int(10,99).random_int(10,99);
        }

        return str($this->faker->sentence(3))->substr(0, $obj->getLength());
    }
}
