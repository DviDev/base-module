<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Modules\Base\Console\DispatchBaseEventsCommand;
use Modules\Base\Events\InstallFinishedEvent;
use Modules\DBMap\Events\ScanTableEvent;

uses(Tests\TestCase::class);

describe('base.commands', function (): void {
    beforeEach(function (): void {
        uses(DatabaseTransactions::class);
    });
    it('DispatchBaseEventsCommand should dispatch some events', function (): void {
        Event::fake();

        $this->artisan(DispatchBaseEventsCommand::class)->assertSuccessful();

        Event::assertDispatched(ScanTableEvent::class);
        Event::assertDispatched(InstallFinishedEvent::class);
    });
});
