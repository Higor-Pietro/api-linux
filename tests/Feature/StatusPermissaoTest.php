<?php

use App\Models\Ticket;
use App\States\Ticket\Aberto;
use App\States\Ticket\EmAndamento;
use App\States\Ticket\Fechado;
use App\States\Ticket\Resolvido;

describe('regra do Operador', function () {
    test('a Policy nega ao Operador e permite ao Admin alterar o status', function () {
        $operador = operador();
        $ticket = Ticket::factory()->for($operador, 'responsavel')->create();

        expect($operador->can('alterarStatus', $ticket))->toBeFalse()
            ->and(admin()->can('alterarStatus', $ticket))->toBeTrue();
    });

    test('a tela de edição não oferece o status ao Operador', function () {
        $operador = operador();
        $ticket = Ticket::factory()->for($operador, 'responsavel')->create();

        $this->actingAs($operador)
            ->get(route('tickets.edit', $ticket))
            ->assertOk()
            ->assertSee('Você não tem permissão para alterar o status.');
    });

    test('a tela de edição oferece o status ao Admin', function () {
        $ticket = Ticket::factory()->create();

        $this->actingAs(admin())
            ->get(route('tickets.edit', $ticket))
            ->assertOk()
            ->assertDontSee('Você não tem permissão para alterar o status.');
    });

    test('o Operador não altera o status do próprio ticket, mesmo burlando a tela', function (string $novoStatus) {
        $operador = operador();
        $ticket = Ticket::factory()->for($operador, 'responsavel')->create();

        $this->actingAs($operador)
            ->from(route('tickets.edit', $ticket))
            ->put(route('tickets.update', $ticket), [
                'descricao' => $ticket->descricao,
                'prioridade' => $ticket->prioridade->value,
                'status' => $novoStatus,
            ])
            ->assertSessionHasErrors([
                'status' => 'Você não tem permissão para alterar o status do ticket.',
            ]);

        expect($ticket->refresh()->status)->toBeInstanceOf(Aberto::class);
    })->with([
        'para em andamento' => [EmAndamento::$name],
        'para resolvido' => [Resolvido::$name],
        'para fechado' => [Fechado::$name],
    ]);

    test('o Admin altera o status (controle)', function () {
        $ticket = Ticket::factory()->create();

        $this->actingAs(admin())
            ->from(route('tickets.edit', $ticket))
            ->put(route('tickets.update', $ticket), [
                'titulo' => $ticket->titulo,
                'descricao' => $ticket->descricao,
                'prioridade' => $ticket->prioridade->value,
                'responsavel_id' => $ticket->responsavel_id,
                'status' => EmAndamento::$name,
            ])
            ->assertSessionHasNoErrors();

        expect($ticket->refresh()->status)->toBeInstanceOf(EmAndamento::class);
    });
});