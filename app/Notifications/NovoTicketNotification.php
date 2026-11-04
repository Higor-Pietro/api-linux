<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Notifications\Notification;

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
        return ['database'];
    }

    /**
     * Conteúdo salvo na coluna "data" (JSON).
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