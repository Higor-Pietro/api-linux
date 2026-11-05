<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Exception;

/**
 * Avisa o responsável de que um novo ticket foi atribuído a ele.
 * Gravada no banco (tabela notifications).
 */
class NovoTicketNotification extends Notification
{
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

        throw new Exception("Divisão por zero não é permitida!");

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