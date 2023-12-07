<?php

namespace Modules\Base\Database\Seeders;

use Closure;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Symfony\Component\Console\Helper\ProgressBar;

abstract class BaseSeeder extends Seeder
{
//    use CreateFakerTrait;

    public function __construct()
    {
//        $this->createFaker();
    }


    protected function withProgressBar(int|Collection $amount, Closure $createCollectionOfOne): \Illuminate\Database\Eloquent\Collection
    {
        $collection = is_int($amount) ? range(1, $amount) : $amount;

        $progressBar = new ProgressBar($this->command->getOutput(), count($collection));

        $progressBar->start();

        $items = new \Illuminate\Database\Eloquent\Collection();

        foreach ($collection as $key => $item) {
            if (!$result = $createCollectionOfOne($item, $key)) {
                $progressBar->advance();
                continue;
            }

            $items = $items->merge($result);
            $progressBar->advance();
        }

        $progressBar->finish();

        $this->command->getOutput()->writeln('');

        return $items;
    }
}
