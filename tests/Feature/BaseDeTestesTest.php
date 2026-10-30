<?php

use App\Enums\Permissao;
use App\Models\Ticket;
use App\States\Ticket\Aberto;

test('o seeder de perfis e permissões roda em todo teste', function () {
    expect(admin()->checkPermissionTo(Permissao::GerenciarUsuarios->value))->toBeTrue();
    expect(operador()->checkPermissionTo(Permissao::GerenciarUsuarios->value))->toBeFalse();
});

test('a factory cria um ticket válido, aberto e com responsável', function () {
    $operador = operador();

    $ticket = Ticket::factory()->for($operador, 'responsavel')->create();

    expect($ticket->status)->toBeInstanceOf(Aberto::class)
        ->and($ticket->responsavel->is($operador))->toBeTrue();
});