<?php

namespace Modules\Base\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Base\Listeners\CreateMenuItemsBaseListener;
use Modules\Project\Events\CreateMenuItemsEvent;

class BaseEventServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     */
    public function register(): void
    {
        \Event::listen(CreateMenuItemsEvent::class, CreateMenuItemsBaseListener::class);
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [];
    }
}
