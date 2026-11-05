<?php

use App\Livewire\AvisoDeTicketNoNavegador;
use App\Models\Ticket;
use App\Notifications\NovoTicketNotification;
use Livewire\Livewire;

test('ignora notificações antigas ao abrir a página', function () {
    $operador = operador();
    $ticket = Ticket::factory()->create(['responsavel_id' => $operador->id]);
    $operador->notify(new NovoTicketNotification($ticket));

    Livewire::actingAs($operador)
        ->test(AvisoDeTicketNoNavegador::class)
        ->call('verificar')
        ->assertNotDispatched('nova-notificacao');
});

test('avisa o navegador sobre uma notificação nova', function () {
    $operador = operador();
    $componente = Livewire::actingAs($operador)->test(AvisoDeTicketNoNavegador::class);

    $ticket = Ticket::factory()->create(['responsavel_id' => $operador->id]);
    $operador->notify(new NovoTicketNotification($ticket));

    $componente->call('verificar')->assertDispatched('nova-notificacao');
});

test('não repete o aviso da mesma notificação', function () {
    $operador = operador();
    $componente = Livewire::actingAs($operador)->test(AvisoDeTicketNoNavegador::class);

    $ticket = Ticket::factory()->create(['responsavel_id' => $operador->id]);
    $operador->notify(new NovoTicketNotification($ticket));

    $componente->call('verificar')
        ->call('verificar')
        ->assertNotDispatched('nova-notificacao');
});