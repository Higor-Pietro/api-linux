<?php

use App\Enums\Prioridade;
use App\Models\Ticket;
use App\States\Ticket\Aberto;
use App\States\Ticket\Fechado;

describe('criação', function () {
    test('o Admin cria um ticket, que nasce Aberto', function () {
        $responsavel = operador();

        $this->actingAs(admin())
            ->from(route('tickets.create'))
            ->post(route('tickets.store'), [
                'titulo' => 'Impressora travando',
                'descricao' => 'A impressora do 2º andar trava ao imprimir em frente e verso.',
                'prioridade' => Prioridade::Alta->value,
                'responsavel_id' => $responsavel->id,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('tickets.index'));

        $ticket = Ticket::where('titulo', 'Impressora travando')->first();

        expect($ticket)->not->toBeNull()
            ->and($ticket->status)->toBeInstanceOf(Aberto::class)
            ->and($ticket->prioridade)->toBe(Prioridade::Alta)
            ->and($ticket->responsavel->is($responsavel))->toBeTrue();
    });

    test('um status enviado na criação é ignorado', function () {
        $this->actingAs(admin())
            ->post(route('tickets.store'), [
                'titulo' => 'Tentando nascer fechado',
                'descricao' => 'Descrição qualquer.',
                'prioridade' => Prioridade::Baixa->value,
                'responsavel_id' => operador()->id,
                'status' => Fechado::$name,
            ])
            ->assertSessionHasNoErrors();

        expect(Ticket::where('titulo', 'Tentando nascer fechado')->first()->status)
            ->toBeInstanceOf(Aberto::class);
    });

    test('o Operador não pode criar tickets', function () {
        $operador = operador();

        $this->actingAs($operador)
            ->post(route('tickets.store'), [
                'titulo' => 'Ticket do operador',
                'descricao' => 'Não deveria ser criado.',
                'prioridade' => Prioridade::Baixa->value,
                'responsavel_id' => $operador->id,
            ])
            ->assertForbidden();

        expect(Ticket::count())->toBe(0);
    });

    test('recusa dados inválidos', function (string $campo, mixed $valor) {
        $dados = [
            'titulo' => 'Título válido',
            'descricao' => 'Descrição válida.',
            'prioridade' => Prioridade::Media->value,
            'responsavel_id' => operador()->id,
        ];

        $dados[$campo] = $valor;

        $this->actingAs(admin())
            ->from(route('tickets.create'))
            ->post(route('tickets.store'), $dados)
            ->assertSessionHasErrors($campo);

        expect(Ticket::count())->toBe(0);
    })->with([
        'título vazio' => ['titulo', ''],
        'descrição vazia' => ['descricao', ''],
        'prioridade inexistente' => ['prioridade', 'altissima'],
        'responsável inexistente' => ['responsavel_id', 99999],
    ]);
});

describe('edição', function () {
    test('o Admin edita qualquer ticket', function () {
        $ticket = Ticket::factory()->for(operador(), 'responsavel')->create([
            'prioridade' => Prioridade::Baixa,
        ]);
        $novoResponsavel = operador();

        $this->actingAs(admin())
            ->from(route('tickets.edit', $ticket))
            ->put(route('tickets.update', $ticket), [
                'titulo' => 'Título corrigido',
                'descricao' => 'Descrição corrigida.',
                'prioridade' => Prioridade::Urgente->value,
                'responsavel_id' => $novoResponsavel->id,
                'status' => $ticket->status->getValue(),
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('tickets.index'));

        $ticket->refresh();

        expect($ticket->titulo)->toBe('Título corrigido')
            ->and($ticket->prioridade)->toBe(Prioridade::Urgente)
            ->and($ticket->responsavel->is($novoResponsavel))->toBeTrue();
    });

    test('o Operador edita a descrição e a prioridade do próprio ticket', function () {
        $operador = operador();
        $ticket = Ticket::factory()->for($operador, 'responsavel')->create([
            'prioridade' => Prioridade::Baixa,
        ]);

        $this->actingAs($operador)
            ->from(route('tickets.edit', $ticket))
            ->put(route('tickets.update', $ticket), [
                'descricao' => 'Atualizei a descrição.',
                'prioridade' => Prioridade::Alta->value,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('tickets.index'));

        $ticket->refresh();

        expect($ticket->descricao)->toBe('Atualizei a descrição.')
            ->and($ticket->prioridade)->toBe(Prioridade::Alta);
    });

    test('o Operador não edita o ticket de outro usuário', function () {
        $ticketDeOutro = Ticket::factory()->for(operador(), 'responsavel')->create();
        $descricaoOriginal = $ticketDeOutro->descricao;

        $this->actingAs(operador())
            ->get(route('tickets.edit', $ticketDeOutro))
            ->assertForbidden();

        $this->actingAs(operador())
            ->put(route('tickets.update', $ticketDeOutro), [
                'descricao' => 'Invasão',
                'prioridade' => Prioridade::Baixa->value,
            ])
            ->assertForbidden();

        expect($ticketDeOutro->refresh()->descricao)->toBe($descricaoOriginal);
    });

    test('o Operador não altera título nem responsável, mesmo burlando a tela', function () {
        $operador = operador();
        $ticket = Ticket::factory()->for($operador, 'responsavel')->create();
        $tituloOriginal = $ticket->titulo;

        $this->actingAs($operador)
            ->from(route('tickets.edit', $ticket))
            ->put(route('tickets.update', $ticket), [
                'titulo' => 'Título alterado na marra',
                'descricao' => $ticket->descricao,
                'prioridade' => $ticket->prioridade->value,
                'responsavel_id' => operador()->id,
            ])
            ->assertSessionHasErrors(['titulo', 'responsavel_id']);

        $ticket->refresh();

        expect($ticket->titulo)->toBe($tituloOriginal)
            ->and($ticket->responsavel->is($operador))->toBeTrue();
    });
});