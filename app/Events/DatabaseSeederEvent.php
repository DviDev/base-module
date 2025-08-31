<?php

namespace Modules\Base\Events;

use Illuminate\Queue\SerializesModels;

class DatabaseSeederEvent
{
    use SerializesModels;

    /**
     * Get the channels the event should be broadcast on.
     */
    public function broadcastOn(): array
    {
        return [];
    }
}
