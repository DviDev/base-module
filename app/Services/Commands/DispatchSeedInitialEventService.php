<?php

namespace Base\app\Services\Commands;

class DispatchSeedInitialEventService
{
    public function handle()
    {
        \Artisan::call('base:dispatch_seed_initial_data_event');
    }
}
