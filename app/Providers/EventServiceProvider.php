<?php

namespace Modules\Base\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Base\Events\BaseSeederInitialIndependentDataEvent;
use Modules\Base\Events\SeederFinishedEvent;
use Modules\Base\Listeners\CreateMenuItemsBaseListener;
use Modules\Base\Listeners\CreateProjectDataListener;
use Modules\Base\Listeners\SeederInitialIndependentDataBaseListener;
use Modules\Project\Events\CreateMenuItemsEvent;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [];

    /**
     * Indicates if events should be discovered.
     *
     * @var bool
     */
    protected static $shouldDiscoverEvents = true;

    /**
     * Configure the proper event listeners for email verification.
     */
    protected function configureEmailVerification(): void {}

    public function register(): void
    {
        \Event::listen(BaseSeederInitialIndependentDataEvent::class, SeederInitialIndependentDataBaseListener::class);
        \Event::listen(CreateMenuItemsEvent::class, CreateMenuItemsBaseListener::class);
        \Event::listen(SeederFinishedEvent::class, CreateProjectDataListener::class);
    }
}
