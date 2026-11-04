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

    /**
     * Tentativas antes de ir para failed_jobs.
     */
    public int $tries = 3;

    /**
     * Se o ticket for excluído antes do processamento, descarta o job.
     */
    public bool $deleteWhenMissingModels = true;

    public function __construct(public Ticket $ticket) {}

    public function handle(): void
    {
        $this->ticket->responsavel->notify(new NovoTicketNotification($this->ticket));
    }
}