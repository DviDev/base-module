<?php

namespace Modules\Base\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Base\Events\BaseSeederInitialIndependentDataEvent;
use Modules\Base\Events\SeederFinishedEvent;
use Modules\Base\Listeners\CreateMenuItemsBaseListener;
use Modules\Base\Listeners\CreateProjectDataListener;
use Modules\Base\Listeners\DefineSearchableAttributes;
use Modules\Base\Listeners\SeederInitialIndependentDataBaseListener;
use Modules\Project\Events\CreateMenuItemsEvent;
use Modules\Project\Events\EntityAttributesCreatedEvent;

class BaseEventServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     */
    public function register(): void
    {
        \Event::listen(BaseSeederInitialIndependentDataEvent::class, SeederInitialIndependentDataBaseListener::class);
        \Event::listen(CreateMenuItemsEvent::class, CreateMenuItemsBaseListener::class);
        \Event::listen(SeederFinishedEvent::class, CreateProjectDataListener::class);
        \Event::listen(EntityAttributesCreatedEvent::class, DefineSearchableAttributes::class);
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [];
    }
}
