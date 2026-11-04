<?php

namespace App\Events;

use App\Models\Ticket;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Disparado quando um ticket é criado pelo sistema.
 * Quem cria o ticket não sabe quem escuta este evento.
 */
class TicketCreated implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public Ticket $ticket) {}
}