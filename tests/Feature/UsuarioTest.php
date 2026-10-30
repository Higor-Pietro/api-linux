<?php

use App\Enums\Perfil;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

describe('criação', function () {
    test('o Admin cria um usuário com perfil', function () {
        $resposta = $this->actingAs(admin())
            ->from(route('usuarios.create'))
            ->post(route('usuarios.store'), [
                'name' => 'Maria Silva',
                'email' => 'maria@teste.com',
                'observacoes' => 'Equipe de suporte',
                'password' => 'senha-segura-123',
                'password_confirmation' => 'senha-segura-123',
                'perfil' => Perfil::Operador->value,
            ]);

        $resposta->assertSessionHasNoErrors()
            ->assertRedirect(route('usuarios.index'));

        $maria = User::where('email', 'maria@teste.com')->first();

        expect($maria)->not->toBeNull()
            ->and($maria->name)->toBe('Maria Silva')
            ->and($maria->hasRole(Perfil::Operador->value))->toBeTrue()
            ->and(Hash::check('senha-segura-123', $maria->password))->toBeTrue();
    });

    test('o Operador não pode criar usuários', function () {
        $this->actingAs(operador())
            ->post(route('usuarios.store'), [
                'name' => 'Intruso',
                'email' => 'intruso@teste.com',
                'password' => 'senha-segura-123',
                'password_confirmation' => 'senha-segura-123',
                'perfil' => Perfil::Admin->value,
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'intruso@teste.com']);
    });

    test('não permite e-mail duplicado', function () {
        User::factory()->create(['email' => 'repetido@teste.com']);

        $this->actingAs(admin())
            ->from(route('usuarios.create'))
            ->post(route('usuarios.store'), [
                'name' => 'Outra Pessoa',
                'email' => 'repetido@teste.com',
                'password' => 'senha-segura-123',
                'password_confirmation' => 'senha-segura-123',
                'perfil' => Perfil::Operador->value,
            ])
            ->assertSessionHasErrors('email');

        expect(User::where('email', 'repetido@teste.com')->count())->toBe(1);
    });
});

describe('edição', function () {
    test('o Admin altera o nome e o perfil de um usuário', function () {
        $usuario = operador();

        $this->actingAs(admin())
            ->from(route('usuarios.edit', $usuario))
            ->put(route('usuarios.update', $usuario), [
                'name' => 'Nome Novo',
                'email' => $usuario->email,
                'perfil' => Perfil::Admin->value,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('usuarios.index'));

        $usuario->refresh();

        expect($usuario->name)->toBe('Nome Novo')
            ->and($usuario->hasRole(Perfil::Admin->value))->toBeTrue()
            ->and($usuario->hasRole(Perfil::Operador->value))->toBeFalse();
    });

    test('senha em branco mantém a senha atual', function () {
        $usuario = operador();
        $senhaAntiga = $usuario->password;

        $this->actingAs(admin())
            ->put(route('usuarios.update', $usuario), [
                'name' => $usuario->name,
                'email' => $usuario->email,
                'password' => '',
                'password_confirmation' => '',
                'perfil' => Perfil::Operador->value,
            ])
            ->assertSessionHasNoErrors();

        expect($usuario->refresh()->password)->toBe($senhaAntiga);
    });

    test('o Admin não pode rebaixar o próprio perfil', function () {
        $admin = admin();

        $this->actingAs($admin)
            ->from(route('usuarios.edit', $admin))
            ->put(route('usuarios.update', $admin), [
                'name' => $admin->name,
                'email' => $admin->email,
                'perfil' => Perfil::Operador->value,
            ])
            ->assertSessionHasErrors('perfil');

        expect($admin->refresh()->hasRole(Perfil::Admin->value))->toBeTrue();
    });
});

describe('exclusão', function () {
    test('o Admin exclui um usuário sem tickets', function () {
        $usuario = operador();

        $this->actingAs(admin())
            ->delete(route('usuarios.destroy', $usuario))
            ->assertRedirect(route('usuarios.index'));

        $this->assertModelMissing($usuario);
    });

    test('não exclui um usuário responsável por tickets', function () {
        $usuario = operador();
        Ticket::factory()->for($usuario, 'responsavel')->create();

        $this->actingAs(admin())
            ->from(route('usuarios.index'))
            ->delete(route('usuarios.destroy', $usuario))
            ->assertSessionHasErrors('usuario');

        $this->assertModelExists($usuario);
    });

    test('o Admin não pode excluir a si mesmo', function () {
        $admin = admin();

        $this->actingAs($admin)
            ->delete(route('usuarios.destroy', $admin))
            ->assertForbidden();

        $this->assertModelExists($admin);
    });
});