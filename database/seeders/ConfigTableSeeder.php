<?php

declare(strict_types=1);

namespace Modules\Base\Database\Seeders;

use Illuminate\Database\Eloquent\Model;
use Modules\Base\Contracts\BaseSeeder;

final class ConfigTableSeeder extends BaseSeeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Model::unguard();

        $this->seed();

    }

    protected function seed(): void
    {
        $this->seeding();
        //
        $this->done();

    }
}
