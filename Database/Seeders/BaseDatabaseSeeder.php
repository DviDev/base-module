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

        $modules = $modules->except('Base');
        $this->call(InitialSeeders::class, parameters: ['modules' => $modules]);
//        $this->call(SecondSeeders::class);

        $this->commandInfo(__CLASS__, '🟢 done');
        /*try {

        } catch (Exception $exception) {
            $this->command->error('🤖 Error when seeding, try again.');
            throw $exception;
        }*/
    }
}
