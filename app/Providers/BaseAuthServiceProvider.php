<?php

declare(strict_types=1);

namespace Modules\Base\Providers;

use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Modules\Person\Enums\UserType;
use Modules\Person\Policies\UserPolicy;

final class BaseAuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        User::class => UserPolicy::class,
    ];

    public function boot(): void
    {
        Gate::define(UserType::SUPER_ADMIN->value, function (User $user) {
            return $user->type->enum() === UserType::SUPER_ADMIN;
        });
        Gate::define(UserType::ADMIN->value, function (User $user) {
            return $user->type->enum() === UserType::ADMIN;
        });
    }
}
