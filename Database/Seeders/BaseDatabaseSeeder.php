<?php

namespace Modules\Base\Database\Seeders;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Nwidart\Modules\Facades\Module;

class BaseDatabaseSeeder extends BaseSeeder
{
//    use WithoutModelEvents;
    /**
     * @throws Exception
     */
    public function run(): void
    {
        Model::unguard();

        $modules = collect(Module::allEnabled());

        //... some code here

        $this->call(InitialSeeders::class, parameters: ['modules' => $modules]);

        try {
            $this->call(SecondSeeders::class);

            $this->commandInfo(__CLASS__, '🟢 done');
        } catch (Exception $exception) {
            $this->command->error('🤖 Error when seeding, try again.');
            throw $exception;
        }
    }
}
