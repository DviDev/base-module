<?php

namespace Modules\Base\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Base\Database\Seeders\Traits\CreateFakerTrait;

abstract class BaseSeeder extends Seeder
{
    use CreateFakerTrait;

    public function __construct()
    {
        $this->createFaker();
    }

    abstract public function run();
}
