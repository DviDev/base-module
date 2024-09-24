<?php

namespace Modules\Base\Events;

use Illuminate\Queue\SerializesModels;

class UsingSpotlightEvent
{
    use SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public string $uri)
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
