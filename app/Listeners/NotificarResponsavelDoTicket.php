<?php

namespace App\Listeners;

use App\Events\TicketCreated;
use App\Jobs\ProcessarNotificacaoDeTicket;

/**
 * Reage à criação de um ticket despachando o job de notificação.
 * Só enfileira: o trabalho pesado fica com o job, fora da requisição.
 */
class NotificarResponsavelDoTicket
{
    public function handle(TicketCreated $event): void
    {
        ProcessarNotificacaoDeTicket::dispatch($event->ticket);
    }
}