<?php

use App\Enums\Prioridade;
use App\Livewire\ListaDeTickets;
use App\Models\Ticket;
use App\Models\User;
use App\States\Ticket\EmAndamento;
use Livewire\Livewire;
use App\States\Ticket\Aberto;
use App\States\Ticket\Fechado;

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

test('o admin confirma e exclui um ticket', function () {
    $ticket = Ticket::factory()->create(['titulo' => 'Ticket para excluir']);

    Livewire::actingAs(admin())
        ->test(ListaDeTickets::class)
        ->call('confirmarExclusao', $ticket->id)
        ->assertSet('ticketParaExcluirId', $ticket->id)
        ->call('excluir')
        ->assertSet('ticketParaExcluirId', null);

    expect(Ticket::find($ticket->id))->toBeNull();
});

test('cancelar a confirmação não exclui o ticket', function () {
    $ticket = Ticket::factory()->create();

    Livewire::actingAs(admin())
        ->test(ListaDeTickets::class)
        ->call('confirmarExclusao', $ticket->id);

    expect(Ticket::find($ticket->id))->not->toBeNull();
});

test('o operador não pode iniciar a exclusão', function () {
    $ticket = Ticket::factory()->create();

    Livewire::actingAs(operador())
        ->test(ListaDeTickets::class)
        ->call('confirmarExclusao', $ticket->id)
        ->assertForbidden();

    expect(Ticket::find($ticket->id))->not->toBeNull();
});

test('o admin altera o status pela listagem', function () {
    $ticket = Ticket::factory()->create();

    Livewire::actingAs(admin())
        ->test(ListaDeTickets::class)
        ->call('alterarStatus', $ticket->id, EmAndamento::$name);

    expect($ticket->fresh()->status)->toBeInstanceOf(EmAndamento::class);
});

test('uma transição inválida é recusada', function () {
    $ticket = Ticket::factory()->create();

    Livewire::actingAs(admin())
        ->test(ListaDeTickets::class)
        ->call('alterarStatus', $ticket->id, Fechado::$name)
        ->assertStatus(422);

    expect($ticket->fresh()->status)->toBeInstanceOf(Aberto::class);
});

test('o operador não altera o status pela listagem', function () {
    $operador = operador();
    $ticket = Ticket::factory()->create(['responsavel_id' => $operador->id]);

    Livewire::actingAs($operador)
        ->test(ListaDeTickets::class)
        ->call('alterarStatus', $ticket->id, EmAndamento::$name)
        ->assertForbidden();

    expect($ticket->fresh()->status)->toBeInstanceOf(Aberto::class);
});