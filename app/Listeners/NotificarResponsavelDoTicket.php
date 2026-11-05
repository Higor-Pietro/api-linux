<?php

namespace App\Listeners;

use App\Events\TicketCreated;
use App\Notifications\NovoTicketNotification;


class NotificarResponsavelDoTicket
{
    public function handle(TicketCreated $event): void
    {
        $event->ticket->responsavel->notify(new NovoTicketNotification($event->ticket));
    }
}