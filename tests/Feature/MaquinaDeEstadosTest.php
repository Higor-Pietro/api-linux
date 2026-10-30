<?php

use App\Models\Ticket;
use App\Services\TicketService;
use App\States\Ticket\Aberto;
use App\States\Ticket\EmAndamento;
use App\States\Ticket\Fechado;
use App\States\Ticket\Resolvido;
use Spatie\ModelStates\Exceptions\CouldNotPerformTransition;

/**
 * Os estados do ticket, na ordem do fluxo.
 */
dataset('estados', [
    'Aberto' => [Aberto::class],
    'Em andamento' => [EmAndamento::class],
    'Resolvido' => [Resolvido::class],
    'Fechado' => [Fechado::class],
]);

/**
 * Todos os pares (de → para) entre estados diferentes: 4 × 3 = 12 transições.
 */
dataset('transicoes', function () {
    $estados = [Aberto::class, EmAndamento::class, Resolvido::class, Fechado::class];

    foreach ($estados as $de) {
        foreach ($estados as $para) {
            if ($de !== $para) {
                yield class_basename($de).' → '.class_basename($para) => [$de, $para];
            }
        }
    }
});

test('todo ticket nasce Aberto', function () {
    expect(Ticket::factory()->create()->status)->toBeInstanceOf(Aberto::class);
});

test('a transição é permitida e gravada no banco', function (string $de, string $para) {
    $ticket = Ticket::factory()->create(['status' => $de]);

    expect($ticket->status->canTransitionTo($para))->toBeTrue();

    $ticket->status->transitionTo($para);

    expect($ticket->refresh()->status)->toBeInstanceOf($para);
    $this->assertDatabaseHas('tickets', ['id' => $ticket->id, 'status' => $para::$name]);
})->with('transicoes');

test('de cada estado há 3 destinos, e nenhum é o próprio estado', function (string $estado) {
    $ticket = Ticket::factory()->create(['status' => $estado]);

    expect($ticket->status->transitionableStates())
        ->toHaveCount(3)
        ->not->toContain($estado::$name);
})->with('estados');

test('a máquina recusa "transicionar" para o mesmo estado', function () {
    $ticket = Ticket::factory()->create();

    expect($ticket->status->canTransitionTo(Aberto::class))->toBeFalse();

    expect(fn () => $ticket->status->transitionTo(Aberto::class))
        ->toThrow(CouldNotPerformTransition::class);
});

test('o Service salva sem erro quando o status não muda', function () {
    $ticket = Ticket::factory()->create();

    app(TicketService::class)->atualizar($ticket, [
        'titulo' => 'Título atualizado',
        'descricao' => $ticket->descricao,
        'prioridade' => $ticket->prioridade->value,
        'responsavel_id' => $ticket->responsavel_id,
        'status' => Aberto::$name,
    ]);

    $ticket->refresh();

    expect($ticket->titulo)->toBe('Título atualizado')
        ->and($ticket->status)->toBeInstanceOf(Aberto::class);
});

test('a edição recusa um status inexistente', function () {
    $ticket = Ticket::factory()->create();

    $this->actingAs(admin())
        ->from(route('tickets.edit', $ticket))
        ->put(route('tickets.update', $ticket), [
            'titulo' => $ticket->titulo,
            'descricao' => $ticket->descricao,
            'prioridade' => $ticket->prioridade->value,
            'responsavel_id' => $ticket->responsavel_id,
            'status' => 'pausado',
        ])
        ->assertSessionHasErrors(['status' => 'Transição de status não permitida.']);

    expect($ticket->refresh()->status)->toBeInstanceOf(Aberto::class);
});