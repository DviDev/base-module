<?php

declare(strict_types=1);

namespace Modules\Base\Providers;

use Event;
use Illuminate\Support\ServiceProvider;
use Modules\Base\Events\SeedInitialIndependentDataEvent;
use Modules\Base\Listeners\CreateMenuItemsBaseListener;
use Modules\Base\Listeners\DefineSearchableAttributes;
use Modules\Base\Listeners\SeederInitialIndependentDataBaseListener;
use Modules\Project\Events\CreateMenuItemsEvent;
use Modules\View\Events\DefineSearchableAttributesEvent;

final class BaseEventServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     */
    public function register(): void
    {
        Event::listen(SeedInitialIndependentDataEvent::class, SeederInitialIndependentDataBaseListener::class);
        Event::listen(CreateMenuItemsEvent::class, CreateMenuItemsBaseListener::class);
        Event::listen(DefineSearchableAttributesEvent::class, DefineSearchableAttributes::class);
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [];
    }
}
