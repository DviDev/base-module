<?php

namespace Modules\Base\Factories;

use App\Models\User;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\BigIntType;
use Doctrine\DBAL\Types\BooleanType;
use Doctrine\DBAL\Types\DateTimeType;
use Doctrine\DBAL\Types\DecimalType;
use Doctrine\DBAL\Types\IntegerType;
use Doctrine\DBAL\Types\SmallIntType;
use Doctrine\DBAL\Types\StringType;
use Doctrine\DBAL\Types\TextType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Base\Models\BaseModel;
use Nwidart\Modules\Facades\Module;

abstract class BaseFactory extends Factory
{
    public function createFn(\Closure $fn)
    {
        /**@var BaseModel $model*/
        $model = new $this->model;
        $entity_class = $model->modelEntity();
        $entity = new $entity_class;
        return parent::create($fn($entity->props()));
    }

    /**
     * @return string
     */
    protected function createName(): string
    {
        return $this->removeAbreviations($this->faker->name())->trim()->value();
    }

    /**
     * @param string $name
     * @return string
     */
    protected function getEmail(string $name): string
    {
        return str(iconv('UTF-8', 'ASCII//TRANSLIT', $this->removeAbreviations($name)))
                ->lower()->explode(' ')->shift(3)->join('_') . '@gmail.com';
    }

    /**
     * @return \Illuminate\Support\Stringable
     */
    protected function removeAbreviations(string $str)
    {
        return str($str)
            ->replace(['Dr.', 'Dra.', 'Sr.', 'Sra.', 'Srta.', 'Jr.', ' da', ' de'], '')->trim();
    }

    protected function getValues(): array
    {
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
            if ($column->getName() == 'id') {
                continue;
            }

            $columns[$column->getName()]['obj'] = $column;
            $columns[$column->getName()]['value'] = null;
            $columns[$column->getName()]['required'] = $column->getNotnull();
        }

        //2 define valores para campos de chave estrangeira
        $table_models = $this->getTableModels();
        $fks = \Schema::getConnection()
            ->getDoctrineSchemaManager()
            ->listTableForeignKeys($entity->table);
        foreach ($fks as $fk) {
            $column = $fk->getLocalColumns()[0];

            if (collect($columns)->contains(function ($col) use ($column) {
                /**@var Column $obj*/
                $obj = $col['obj'];
                return $obj->getName() == $column;
            })){
                //se houver uma chave estrangeira para msm tabela o que eh comum em
                //campos ex. parent_id, irá causar um loop infinito
                //deve verificar se a tabela é a mesma
                $foreignTableName = $fk->getForeignTableName();
                if ($foreignTableName == $entity->table) {
                    $columns[$column]['value'] = $model::query()->inRandomOrder()->first()->id ?? null;
                    continue;
                }
                if (!isset($table_models[$foreignTableName]) && config('app.env') == 'local') {
                    ds($table_models);
                }
                $model = $table_models[$foreignTableName];
                $columns[$column]['value'] = $model::factory()->create()->id;
            }
        }

        //define valores para os campos opcionais
        $another_columns = collect($columns)->filter(fn($c) => empty($c['value']))->toArray();
        foreach ($another_columns as $key => $item) {
            /**@var Column $obj*/
            $obj = $item['obj'];
            if ($obj->getName() == 'deleted_at') {
                continue;
            }
            if (!$item['required'] && !$this->faker->boolean) {
                continue;
            }
            $type = (new \ReflectionObject($obj->getType()))->getName();
            $another_columns[$key]['value'] = match ($type) {
                DateTimeType::class => now(),
                TextType::class => $this->faker->sentence(),
                StringType::class => $this->faker->sentence(3),
                BooleanType::class => $this->faker->boolean,
                DecimalType::class => $this->faker->randomFloat(2, 1, 999999),
                SmallIntType::class, IntegerType::class, BigIntType::class => $this->faker->numberBetween(1, 90),
                default => 1
            };
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
                ds('Não tem modulos habilitados');
                ds(Module::allEnabled());
            }
            $table_model = [];
            /**@var \Nwidart\Modules\Laravel\Module $module */
            foreach ($modules as $module) {
                if ($module->getName() == 'Base') {
                    continue;
                }
                $module_path = 'Modules/' . $module->getName() . '/Models';
                if (is_dir(base_path($module_path))) {
                    $files = \File::files(base_path($module_path));
                    foreach ($files as $file) {
                        /**@var BaseModel $model_f*/
                        $model_f = str($module_path . '/' . $file->getFilenameWithoutExtension())->replace('/', '\\')->value();

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

        return cache()->rememberForever('dvi_table_models', $fn);
    }
}
