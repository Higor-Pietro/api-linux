<?php

namespace App\Livewire;

use App\Models\Ticket;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class SinoDeNotificacoes extends Component
{
    public function abrir(string $id): void
    {
        $notificacao = auth()->user()->notifications()->findOrFail($id);
        $notificacao->markAsRead();

        $ticket = Ticket::find($notificacao->data['ticket_id']);

        if ($ticket && Gate::allows('update', $ticket)) {
            $this->redirectRoute('tickets.edit', ['ticket' => $ticket->id], navigate: true);

            return;
        }

        $this->redirectRoute('tickets.index', navigate: true);
    }

    public function render(): View
    {
        $naoLidas = auth()->user()->unreadNotifications();

        return view('livewire.sino-de-notificacoes', [
            'total' => $naoLidas->count(),
            'notificacoes' => $naoLidas->take(10)->get(),
        ]);
    }
}