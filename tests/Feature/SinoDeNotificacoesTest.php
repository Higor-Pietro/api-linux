<?php

use App\Livewire\SinoDeNotificacoes;
use App\Models\Ticket;
use App\Notifications\NovoTicketNotification;
use Livewire\Livewire;

test('o responsável vê a notificação do ticket', function () {
    $operador = operador();
    $ticket = Ticket::factory()->create([
        'responsavel_id' => $operador->id,
        'titulo' => 'Impressora parada',
    ]);

    $operador->notify(new NovoTicketNotification($ticket));

    Livewire::actingAs($operador)
        ->test(SinoDeNotificacoes::class)
        ->assertSee('Impressora parada');
});

test('outro usuário não vê a notificação do responsável', function () {
    $responsavel = operador();
    $outroUsuario = operador();
    $ticket = Ticket::factory()->create([
        'responsavel_id' => $responsavel->id,
        'titulo' => 'Impressora parada',
    ]);

    $responsavel->notify(new NovoTicketNotification($ticket));

    Livewire::actingAs($outroUsuario)
        ->test(SinoDeNotificacoes::class)
        ->assertDontSee('Impressora parada');
});

test('abrir a notificação marca como lida e vai para o ticket', function () {
    $operador = operador();
    $ticket = Ticket::factory()->create(['responsavel_id' => $operador->id]);

    $operador->notify(new NovoTicketNotification($ticket));
    $notificacao = $operador->notifications()->first();

    Livewire::actingAs($operador)
        ->test(SinoDeNotificacoes::class)
        ->call('abrir', $notificacao->id)
        ->assertRedirect(route('tickets.edit', $ticket));

    expect($operador->unreadNotifications()->count())->toBe(0);
});

test('um usuário não abre a notificação de outro', function () {
    $responsavel = operador();
    $outroUsuario = operador();
    $ticket = Ticket::factory()->create(['responsavel_id' => $responsavel->id]);

    $responsavel->notify(new NovoTicketNotification($ticket));
    $notificacao = $responsavel->notifications()->first();

    Livewire::actingAs($outroUsuario)
        ->test(SinoDeNotificacoes::class)
        ->call('abrir', $notificacao->id)
        ->assertStatus(404);

    expect($notificacao->fresh()->read_at)->toBeNull();
});