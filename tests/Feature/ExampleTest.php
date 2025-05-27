<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Modules\Base\Console\DispatchBaseEventsCommand;
use Modules\Base\Events\SeederFinishedEvent;
use Modules\DBMap\Events\ScanTableEvent;

uses(Tests\TestCase::class);
uses(DatabaseTransactions::class);

it('verifica se e verdadeiro', function () {
    expect(true)->toBeTrue();
});

describe('Testes de Arquivos', function () {
    it('verifica se os arquivos existem', function () {
        // Aqui você pode adicionar verificações específicas
        expect(true)->toBeTrue();
    });
});

it('DispatchBaseEventsCommand should dispatch some events', function () {
    Event::fake();

    $this->artisan(DispatchBaseEventsCommand::class)->assertSuccessful();

    Event::assertDispatched(ScanTableEvent::class);
    Event::assertDispatched(SeederFinishedEvent::class);
});
