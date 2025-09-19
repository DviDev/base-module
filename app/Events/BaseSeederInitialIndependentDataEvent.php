<?php

declare(strict_types=1);

namespace Modules\Base\Events;

use Illuminate\Queue\SerializesModels;

final class BaseSeederInitialIndependentDataEvent
{
    use SerializesModels;

    public function broadcastOn(): array
    {
        return [];
    }
}
