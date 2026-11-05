<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;



class NovoTicketNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 30;

    public bool $deleteWhenMissingModels = true;

    public function __construct(private Ticket $ticket) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }


    public function toMail(object $notifiable): MailMessage
    {

        return (new MailMessage)
            ->subject(__('Novo ticket: :titulo', ['titulo' => $this->ticket->titulo]))
            ->greeting(__('Olá, :nome!', ['nome' => $notifiable->name]))
            ->line(__('Um novo ticket foi atribuído a você.'))
            ->line(__('Título: :titulo', ['titulo' => $this->ticket->titulo]))
            ->line(__('Prioridade: :prioridade', ['prioridade' => $this->ticket->prioridade->value]))
            ->action(__('Abrir ticket'), route('tickets.edit', $this->ticket))
            ->salutation(__('Equipe de suporte'));
    }

    /**
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'titulo' => $this->ticket->titulo,
            'prioridade' => $this->ticket->prioridade->value,
            'mensagem' => __('Novo ticket atribuído a você: :titulo', ['titulo' => $this->ticket->titulo]),
        ];
    }
}