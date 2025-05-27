<?php

namespace Modules\Base\Events;

use Illuminate\Queue\SerializesModels;

class SeederFinishedEvent
{
    use SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the channels the event should be broadcast on.
     */
    public function broadcastOn(): array
    {
        return [];
    }
}
