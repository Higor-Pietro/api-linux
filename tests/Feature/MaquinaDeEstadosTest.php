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
 * As transições do fluxo (espelho do TicketState::config()).
 */
dataset('transicoes permitidas', [
    'Aberto → EmAndamento' => [Aberto::class, EmAndamento::class],
    'EmAndamento → Resolvido' => [EmAndamento::class, Resolvido::class],
    'Resolvido → Fechado' => [Resolvido::class, Fechado::class],
    'Fechado → EmAndamento (reabrir)' => [Fechado::class, EmAndamento::class],
]);

/**
 * Todos os pares entre estados diferentes que NÃO estão no fluxo: 12 − 4 = 8.
 */
dataset('transicoes proibidas', function () {
    $estados = [Aberto::class, EmAndamento::class, Resolvido::class, Fechado::class];

    $permitidas = [
        [Aberto::class, EmAndamento::class],
        [EmAndamento::class, Resolvido::class],
        [Resolvido::class, Fechado::class],
        [Fechado::class, EmAndamento::class],
    ];

    foreach ($estados as $de) {
        foreach ($estados as $para) {
            if ($de !== $para && ! in_array([$de, $para], $permitidas, true)) {
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
})->with('transicoes permitidas');


test('a transição fora do fluxo é recusada', function (string $de, string $para) {
    $ticket = Ticket::factory()->create(['status' => $de]);

    expect($ticket->status->canTransitionTo($para))->toBeFalse();

    expect(fn () => $ticket->status->transitionTo($para))
        ->toThrow(CouldNotPerformTransition::class);

    expect($ticket->refresh()->status)->toBeInstanceOf($de);
})->with('transicoes proibidas');


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

test('nem o Admin pula etapas do fluxo pela edição', function () {
    $ticket = Ticket::factory()->create();

    $this->actingAs(admin())
        ->from(route('tickets.edit', $ticket))
        ->put(route('tickets.update', $ticket), [
            'titulo' => $ticket->titulo,
            'descricao' => $ticket->descricao,
            'prioridade' => $ticket->prioridade->value,
            'responsavel_id' => $ticket->responsavel_id,
            'status' => Fechado::$name,
        ])
        ->assertSessionHasErrors(['status' => 'Transição de status não permitida.']);

    expect($ticket->refresh()->status)->toBeInstanceOf(Aberto::class);
});