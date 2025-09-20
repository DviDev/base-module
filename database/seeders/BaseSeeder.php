<?php

declare(strict_types=1);

namespace Modules\Base\Database\Seeders;

use Closure;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Helper\ProgressBar;

abstract class BaseSeeder extends Seeder
{
    protected function withProgressBar(int|Collection $amount, Closure $createCollectionOfOne): \Illuminate\Database\Eloquent\Collection
    {
        $collection = is_int($amount) ? range(1, $amount) : $amount;

        $progressBar = new ProgressBar($this->command->getOutput(), count($collection));

        $progressBar->start();

        $items = new \Illuminate\Database\Eloquent\Collection;

        foreach ($collection as $key => $item) {
            if (! $result = $createCollectionOfOne($item, $key)) {
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

    protected function seeding(): void
    {
        $class = get_called_class();
        $this->commandInfo($class, '💦 🌱 seeding ...');
    }

    protected function done(): void
    {
        $class = get_called_class();
        $this->commandInfo($class, '✅ done');
    }

    protected function commandWarn(string $target, ?string $label = null): void
    {
        $item = str($target)->explode('\\');
        $msg = collect('🤖')
            ->add($item->last())
            ->add('('.$item->slice(1, 1)->first().')')
            ->add($label)
            ->join(' ');
        Log::warning($msg);
        $this->command->warn(PHP_EOL.$msg);
    }

    protected function commandInfo(string $target, ?string $label = null): void
    {
        $item = str($target)->explode('\\');
        $msg = collect('🤖')
            ->add($item->last())
            ->add('('.$item->slice(1, 1)->first().')')
            ->add($label)
            ->join(' ');

        Log::info($msg);
        $this->command->info(PHP_EOL.$msg);
    }
}
