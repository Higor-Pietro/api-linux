<?php

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Notifications\DatabaseNotificationCollection;
use Livewire\Component;

class AvisoDeTicketNoNavegador extends Component
{
    /**
     * Notificações recentes que este navegador já conhece.
     *
     * @var array<int, string>
     */
    public array $idsConhecidos = [];

    public function mount(): void
    {
        $this->idsConhecidos = $this->recentes()->pluck('id')->all();
    }

    public function verificar(): void
    {
        $recentes = $this->recentes();

        foreach ($recentes->whereNotIn('id', $this->idsConhecidos) as $notificacao) {
            $this->dispatch(
                'nova-notificacao',
                id: $notificacao->id,
                mensagem: $notificacao->data['mensagem'],
            );
        }

        $this->idsConhecidos = $recentes->pluck('id')->all();
    }

    public function render(): View
    {
        return view('livewire.aviso-de-ticket-no-navegador');
    }

    private function recentes(): DatabaseNotificationCollection
    {
        return auth()->user()->unreadNotifications()->take(20)->get();
    }
}