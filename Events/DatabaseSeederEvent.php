<?php

namespace Modules\Base\Events;

use Illuminate\Console\Command;
use Illuminate\Queue\SerializesModels;

class DatabaseSeederEvent
{
    use SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public Command $command)
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
