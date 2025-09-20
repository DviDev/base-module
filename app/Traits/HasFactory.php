<?php

declare(strict_types=1);

namespace Modules\Base\Traits;

use Illuminate\Support\Collection;
use Modules\Base\Factories\BaseFactory;

trait HasFactory
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected static function newFactory()
    {
        return new class(model: get_called_class()) extends BaseFactory
        {
            public $model;

            public function __construct($count = null, ?Collection $states = null, ?Collection $has = null, ?Collection $for = null, ?Collection $afterMaking = null, ?Collection $afterCreating = null, $connection = null, ?Collection $recycle = null, $model = null)
            {
                $this->model = $model;
                parent::__construct($count, $states, $has, $for, $afterMaking, $afterCreating, $connection, $recycle);
            }

            protected function newInstance(array $arguments = [])
            {
                $new = parent::newInstance($arguments);
                $new->model = $this->model;

                return $new;
            }
        };
    }
}
