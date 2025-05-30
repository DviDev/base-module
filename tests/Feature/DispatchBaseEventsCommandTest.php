<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Modules\Base\Console\DispatchBaseEventsCommand;
use Modules\Base\Events\SeederFinishedEvent;
use Modules\DBMap\Events\ScanTableEvent;

uses(Tests\TestCase::class);

describe('base.commands', function () {
    beforeEach(function () {
        uses(DatabaseTransactions::class);
    });
    it('DispatchBaseEventsCommand should dispatch some events', function () {
        Event::fake();

        $this->artisan(DispatchBaseEventsCommand::class)->assertSuccessful();

        Event::assertDispatched(ScanTableEvent::class);
        Event::assertDispatched(SeederFinishedEvent::class);
    });
});
