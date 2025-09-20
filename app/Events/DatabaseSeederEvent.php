<?php

declare(strict_types=1);

namespace Modules\Base\Events;

use Illuminate\Queue\SerializesModels;

final class DatabaseSeederEvent
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
