<?php

namespace App\Jobs;

use App\Models\Ticket;
use App\Notifications\NovoTicketNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Gera a notificação do novo ticket em segundo plano (fila Redis).
 */
class ProcessarNotificacaoDeTicket implements ShouldQueue
{
    use Queueable;

    
    public int $tries = 3;

    
    public int $backoff = 30;

    
    public bool $deleteWhenMissingModels = true;

    public function __construct(public Ticket $ticket) {}

    public function handle(): void
    {
        $this->ticket->responsavel->notify(new NovoTicketNotification($this->ticket));
    }
}