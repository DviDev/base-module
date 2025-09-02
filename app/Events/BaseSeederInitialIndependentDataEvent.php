<?php

namespace Modules\Base\Events;

use Illuminate\Queue\SerializesModels;

class BaseSeederInitialIndependentDataEvent
{
    use SerializesModels;

    public function broadcastOn(): array
    {
        return [];
    }
}
