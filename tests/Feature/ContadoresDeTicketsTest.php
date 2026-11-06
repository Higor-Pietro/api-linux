<?php

use App\Livewire\ContadoresDeTickets;
use App\Models\Ticket;
use App\Models\User;
use App\States\Ticket\Aberto;
use App\States\Ticket\EmAndamento;
use App\States\Ticket\Resolvido;
use Livewire\Livewire;
use App\States\Ticket\Fechado;

test('o admin vê os contadores de todos os tickets', function () {
    Ticket::factory()->count(2)->create();

    $emAndamento = Ticket::factory()->create();
    $emAndamento->status->transitionTo(EmAndamento::class);

    Livewire::actingAs(admin())
        ->test(ContadoresDeTickets::class)
        ->assertViewHas('contadores', fn ($contadores) => $contadores->pluck('total', 'chave')->all() === [
            Aberto::$name => 2,
            EmAndamento::$name => 1,
            Resolvido::$name => 0,
            Fechado::$name => 0,    
        ]);
});

test('o operador vê só os contadores dos seus tickets', function () {
    $operador = operador();

    Ticket::factory()->create(['responsavel_id' => $operador->id]);
    Ticket::factory()->create();

    Livewire::actingAs($operador)
        ->test(ContadoresDeTickets::class)
        ->assertViewHas('contadores', fn ($contadores) => $contadores->firstWhere('chave', Aberto::$name)['total'] === 1);
});

test('os contadores mudam ao criar um novo ticket', function () {
    $componente = Livewire::actingAs(admin())->test(ContadoresDeTickets::class);

    Ticket::factory()->create();

    $componente->call('$refresh')
        ->assertViewHas('contadores', fn ($contadores) => $contadores->firstWhere('chave', Aberto::$name)['total'] === 1);
});

test('usuário sem permissão não acessa os contadores', function () {
    Livewire::actingAs(User::factory()->create())
        ->test(ContadoresDeTickets::class)
        ->assertForbidden();
});