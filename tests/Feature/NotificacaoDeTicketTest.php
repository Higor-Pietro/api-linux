<?php

use App\Enums\Prioridade;
use App\Events\TicketCreated;
use Illuminate\Notifications\SendQueuedNotifications;
use App\Listeners\NotificarResponsavelDoTicket;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\NovoTicketNotification;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Notification;

/**
 * Cria um ticket pelo caminho real (rota + FormRequest + Service), logado como Admin.
 */
function criarTicketPelaTela(User $responsavel, string $titulo): void
{
    test()->actingAs(admin())
        ->post(route('tickets.store'), [
            'titulo' => $titulo,
            'descricao' => 'Descrição do problema.',
            'prioridade' => Prioridade::Media->value,
            'responsavel_id' => $responsavel->id,
        ])
        ->assertSessionHasNoErrors();
}

describe('evento', function () {
    test('criar um ticket pela tela dispara o TicketCreated', function () {
        Event::fake([TicketCreated::class]);

        criarTicketPelaTela(operador(), 'Monitor sem imagem');

        Event::assertDispatched(
            TicketCreated::class,
            fn (TicketCreated $evento) => $evento->ticket->titulo === 'Monitor sem imagem',
        );
    });

    test('o NotificarResponsavelDoTicket escuta o TicketCreated', function () {
        Event::fake();

        Event::assertListening(TicketCreated::class, NotificarResponsavelDoTicket::class);
    });

    test('criar um ticket pela factory não dispara o evento', function () {
        Event::fake([TicketCreated::class]);

        Ticket::factory()->create();

        Event::assertNotDispatched(TicketCreated::class);
    });
});

describe('job', function () {
   test('cada canal vai para a fila sem gerar a notificação durante a requisição', function () {
        Queue::fake();
        $responsavel = operador();

        criarTicketPelaTela($responsavel, 'Teclado falhando');

        Queue::assertPushed(
            SendQueuedNotifications::class,
            fn (SendQueuedNotifications $tarefa) => $tarefa->channels === ['mail'],
        );

        Queue::assertPushed(
            SendQueuedNotifications::class,
            fn (SendQueuedNotifications $tarefa) => $tarefa->channels === ['database'],
        );

        expect($responsavel->notifications()->count())->toBe(0);
    });

    test('ao ser processada, a notificação é gravada para o responsável', function () {
        $responsavel = operador();
        $ticket = Ticket::factory()->create([
            'responsavel_id' => $responsavel->id,
            'titulo' => 'Mouse quebrado',
        ]);

        $responsavel->notify(new NovoTicketNotification($ticket));

        $notificacao = $responsavel->notifications()->first();

        expect($notificacao)->not->toBeNull()
            ->and($notificacao->type)->toBe(NovoTicketNotification::class)
            ->and($notificacao->data['ticket_id'])->toBe($ticket->id)
            ->and($notificacao->data['mensagem'])->toContain('Mouse quebrado')
            ->and($notificacao->read_at)->toBeNull();
    });

    test('fluxo completo: criar o ticket gera a notificação do responsável', function () {
        $responsavel = operador();

        criarTicketPelaTela($responsavel, 'Rede lenta');

        expect($responsavel->notifications()->count())->toBe(1)
            ->and($responsavel->notifications()->first()->data['titulo'])->toBe('Rede lenta');
    });

    test('o listener envia o e-mail para o responsável', function () {
        Notification::fake();
        $responsavel = operador();
        $ticket = Ticket::factory()->create(['responsavel_id' => $responsavel->id]);

        (new NotificarResponsavelDoTicket)->handle(new TicketCreated($ticket));

        Notification::assertSentTo(
            $responsavel,
            NovoTicketNotification::class,
            fn ($notificacao, array $canais) => in_array('mail', $canais),
        );
    });

    test('o e-mail traz o título e o link do ticket', function () {
        $responsavel = operador();
        $ticket = Ticket::factory()->create([
            'responsavel_id' => $responsavel->id,
            'titulo' => 'Mouse quebrado',
        ]);

        $email = (new NovoTicketNotification($ticket))->toMail($responsavel);

        expect($email->subject)->toContain('Mouse quebrado')
            ->and($email->actionUrl)->toBe(route('tickets.edit', $ticket));
    });
});