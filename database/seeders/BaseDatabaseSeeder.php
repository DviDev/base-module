<?php

namespace Modules\Base\Database\Seeders;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;

class BaseDatabaseSeeder extends BaseSeeder
{
    //    use WithoutModelEvents;
    /**
     * @throws Exception
     */
    public function run(): void
    {
        Model::unguard();

        cache()->clear();

        $storage_path = storage_path('app/temp_seed_files');
        File::deleteDirectory($storage_path);

        $this->done();
    }
}
