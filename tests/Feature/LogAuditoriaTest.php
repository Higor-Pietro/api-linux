<?php

use App\Enums\AcaoAuditoria;
use App\Enums\Perfil;
use App\Enums\Prioridade;
use App\Models\Ticket;
use App\Models\User;
use App\States\Ticket\EmAndamento;
use Spatie\Activitylog\Models\Activity;

/**
 * Último log de um registro para um evento (created, updated, deleted).
 */
function ultimoLog(Illuminate\Database\Eloquent\Model $registro, AcaoAuditoria $acao): ?Activity
{
    return Activity::query()
        ->forSubject($registro)
        ->forEvent($acao->value)
        ->latest('id')
        ->first();
}

/**
 * Dados completos de edição de um ticket, com sobrescritas.
 */
function dadosDoTicket(Ticket $ticket, array $alteracoes = []): array
{
    return [
        'titulo' => $ticket->titulo,
        'descricao' => $ticket->descricao,
        'prioridade' => $ticket->prioridade->value,
        'responsavel_id' => $ticket->responsavel_id,
        'status' => $ticket->status->getValue(),
        ...$alteracoes,
    ];
}

describe('tickets', function () {
    test('criar um ticket gera log de criação com o autor', function () {
        $admin = admin();

        $this->actingAs($admin)->post(route('tickets.store'), [
            'titulo' => 'Impressora parada',
            'descricao' => 'Não imprime desde ontem.',
            'prioridade' => Prioridade::cases()[0]->value,
            'responsavel_id' => $admin->id,
        ])->assertSessionHasNoErrors();

        $log = ultimoLog(Ticket::firstWhere('titulo', 'Impressora parada'), AcaoAuditoria::Criacao);

        expect($log)->not->toBeNull()
            ->and($log->log_name)->toBe('tickets')
            ->and($log->causer->is($admin))->toBeTrue()
            ->and($log->attribute_changes->get('attributes')['titulo'])->toBe('Impressora parada');
    });

    test('editar um ticket guarda o valor anterior e o novo', function () {
        $admin = admin();
        $ticket = Ticket::factory()->create(['titulo' => 'Título antigo']);

        $this->actingAs($admin)
            ->put(route('tickets.update', $ticket), dadosDoTicket($ticket, ['titulo' => 'Título novo']))
            ->assertSessionHasNoErrors();

        $log = ultimoLog($ticket, AcaoAuditoria::Alteracao);

        expect($log->causer->is($admin))->toBeTrue()
            ->and($log->attribute_changes->get('old')['titulo'])->toBe('Título antigo')
            ->and($log->attribute_changes->get('attributes')['titulo'])->toBe('Título novo');
    });

    test('mudar o status fica registrado no log', function () {
        $ticket = Ticket::factory()->create();

        $this->actingAs(admin())
            ->put(route('tickets.update', $ticket), dadosDoTicket($ticket, ['status' => EmAndamento::$name]))
            ->assertSessionHasNoErrors();

        $log = ultimoLog($ticket, AcaoAuditoria::Alteracao);

        expect($log->attribute_changes->get('old')['status'])->toBe('aberto')
            ->and($log->attribute_changes->get('attributes')['status'])->toBe(EmAndamento::$name);
    });

    test('excluir um ticket gera log de exclusão', function () {
        $ticket = Ticket::factory()->create();

        $this->actingAs(admin())->delete(route('tickets.destroy', $ticket));

        expect(ultimoLog($ticket, AcaoAuditoria::Exclusao))->not->toBeNull();
    });
});

describe('usuários', function () {
    test('editar um usuário gera log e nunca grava a senha', function () {
        $usuario = operador();

        $this->actingAs(admin())
            ->put(route('usuarios.update', $usuario), [
                'name' => 'Nome Novo',
                'email' => $usuario->email,
                'password' => 'nova-senha-segura-123',
                'password_confirmation' => 'nova-senha-segura-123',
                'perfil' => Perfil::Operador->value,
            ])
            ->assertSessionHasNoErrors();

        $log = ultimoLog($usuario, AcaoAuditoria::Alteracao);

        expect($log->log_name)->toBe('usuarios')
            ->and($log->attribute_changes->get('attributes')['name'])->toBe('Nome Novo')
            ->and($log->attribute_changes->get('attributes'))->not->toHaveKey('password')
            ->and($log->attribute_changes->get('old'))->not->toHaveKey('password');
    });

    test('trocar o perfil gera o log "perfil alterado"', function () {
        $admin = admin();
        $usuario = operador();

        $this->actingAs($admin)
            ->put(route('usuarios.update', $usuario), [
                'name' => $usuario->name,
                'email' => $usuario->email,
                'perfil' => Perfil::Admin->value,
            ])
            ->assertSessionHasNoErrors();

        $log = Activity::query()->forSubject($usuario)->where('description', 'perfil alterado')->first();

        expect($log)->not->toBeNull()
            ->and($log->causer->is($admin))->toBeTrue()
            ->and($log->attribute_changes->get('old')['perfil'])->toBe(Perfil::Operador->value)
            ->and($log->attribute_changes->get('attributes')['perfil'])->toBe(Perfil::Admin->value);
    });

    test('manter o perfil não gera o log "perfil alterado"', function () {
        $usuario = operador();

        $this->actingAs(admin())
            ->put(route('usuarios.update', $usuario), [
                'name' => 'Outro Nome',
                'email' => $usuario->email,
                'perfil' => Perfil::Operador->value,
            ])
            ->assertSessionHasNoErrors();

        expect(Activity::query()->forSubject($usuario)->where('description', 'perfil alterado')->exists())
            ->toBeFalse();
    });
});

describe('tela de logs', function () {
    test('só quem tem permissão acessa os logs', function () {
        $this->actingAs(admin())->get(route('auditoria.index'))->assertOk();

        $this->actingAs(operador())->get(route('auditoria.index'))->assertForbidden();
    });
});