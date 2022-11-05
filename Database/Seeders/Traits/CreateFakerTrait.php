<?php

namespace Modules\Base\Database\Seeders\Traits;

use Faker\Factory as Faker;
use Faker\Generator;

trait CreateFakerTrait
{
    /**@var Generator*/
    public $faker;

    public function createFaker()
    {
        $this->faker = Faker::create('pt_BR');
    }
}
