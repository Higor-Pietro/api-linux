<?php

use App\Enums\Prioridade;
use App\Livewire\ListaDeTickets;
use App\Models\Ticket;
use App\Models\User;
use App\States\Ticket\EmAndamento;
use Livewire\Livewire;

test('lista os tickets', function () {
    Ticket::factory()->create(['titulo' => 'Mouse quebrado']);

    Livewire::actingAs(admin())
        ->test(ListaDeTickets::class)
        ->assertSee('Mouse quebrado');
});

test('busca pelo título sem diferenciar maiúsculas', function () {
    Ticket::factory()->create(['titulo' => 'Mouse quebrado']);
    Ticket::factory()->create(['titulo' => 'Teclado falhando']);

    Livewire::actingAs(admin())
        ->test(ListaDeTickets::class)
        ->set('busca', 'MOUSE')
        ->assertSee('Mouse quebrado')
        ->assertDontSee('Teclado falhando');
});

test('filtra por prioridade', function () {
    Ticket::factory()->create([
        'titulo' => 'Servidor fora do ar',
        'prioridade' => Prioridade::Urgente,
    ]);

    Ticket::factory()->create([
        'titulo' => 'Trocar cabo do mouse',
        'prioridade' => Prioridade::Baixa,
    ]);

    Livewire::actingAs(admin())
        ->test(ListaDeTickets::class)
        ->set('prioridade', Prioridade::Urgente->value)
        ->assertSee('Servidor fora do ar')
        ->assertDontSee('Trocar cabo do mouse');
});

test('filtra por status', function () {
    $emAndamento = Ticket::factory()->create(['titulo' => 'Em atendimento']);
    $emAndamento->status->transitionTo(EmAndamento::class);
    Ticket::factory()->create(['titulo' => 'Ainda aberto']);

    Livewire::actingAs(admin())
        ->test(ListaDeTickets::class)
        ->set('status', $emAndamento->fresh()->status->getValue())
        ->assertSee('Em atendimento')
        ->assertDontSee('Ainda aberto');
});

test('usuário sem permissão não acessa a listagem', function () {
    Livewire::actingAs(User::factory()->create())
        ->test(ListaDeTickets::class)
        ->assertForbidden();
});