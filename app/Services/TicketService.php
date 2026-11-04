<?php

namespace App\Services;

use App\Models\Ticket;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use App\Events\TicketCreated;

class TicketService
{
    /**
     * Cria um ticket. O status inicial (Aberto) é definido pela máquina de estados.
     *
     * @param  array{titulo: string, descricao: string, prioridade: string, responsavel_id: int}  $dados
     */
        public function criar(array $dados): Ticket
    {
        $ticket = Ticket::create($dados);

        TicketCreated::dispatch($ticket);

        return $ticket;
    }

    /**
     * @param  array{titulo?: string, descricao: string, prioridade?: string, responsavel_id?: int, status?: string}  $dados
     */
    public function atualizar(Ticket $ticket, array $dados): Ticket
    {
        return DB::transaction(function () use ($ticket, $dados) {
            $ticket->update(Arr::except($dados, ['status']));

            if (isset($dados['status'])) {
                $this->alterarStatus($ticket, $dados['status']);
            }

            return $ticket;
        });
    }

    /**
     * Exclui um ticket.
     */
    public function excluir(Ticket $ticket): void
    {
        $ticket->delete();
    }

    /**
     * Manter o mesmo status não é uma transição; qualquer outro valor passa pela máquina de estados.
     */
    private function alterarStatus(Ticket $ticket, string $novoStatus): void
    {
        if ($ticket->status->equals($novoStatus)) {
            return;
        }

        $ticket->status->transitionTo($novoStatus);
    }
}