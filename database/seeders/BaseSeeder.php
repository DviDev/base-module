<?php

namespace Modules\Base\Database\Seeders;

use App\Models\User;
use Closure;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Modules\Person\Models\PersonModel;
use Modules\Person\Models\UserTypeModel;
use Symfony\Component\Console\Helper\ProgressBar;

abstract class BaseSeeder extends Seeder
{
    public static function firstOrCreateUser(UserTypeModel $type): User
    {
        $user = User::query()->firstWhere('email', $type->name.'@site.com');
        if ($user) {
            return $user;
        }

        $name = str($type->name)->explode('_')->map(fn (string $word) => str($word)->lower()->ucfirst())->join(' ');

        return User::factory()->create([
            'name' => $name,
            'email' => $type->name.'@site.com',
            'password' => \Hash::make('password'),
            'type_id' => $type->id,
            'person_id' => PersonModel::factory()->create(['name' => $name])->id,
        ]);
    }

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

    protected function commandWarn(string $target, ?string $label = null): void
    {
        $this->command->warn(PHP_EOL.collect('🤖')->add($label)->add(str($target)->explode('\\')->last())->add('...')->join(' '));
    }

    protected function commandInfo(mixed $string, ?string $label = null): void
    {
        $this->command->info(PHP_EOL.collect()->add('🤖')->add($label)->add(str($string)->explode('\\')->last())->join(' '));
    }
}
