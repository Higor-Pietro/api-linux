<?php

namespace App\Livewire;

use App\Enums\Prioridade;
use App\Models\Ticket;
use App\States\Ticket\TicketState;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ListaDeTickets extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $busca = '';

    #[Url(except: '')]
    public string $status = '';

    #[Url(except: '')]
    public string $prioridade = '';

    /**
     * Ao mudar um filtro, volta para a primeira página.
     */
    public function updated(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        Gate::authorize('viewAny', Ticket::class);

        $tickets = Ticket::query()
            ->with('responsavel')
            ->when($this->busca !== '', function (Builder $consulta): void {
                $termo = "%{$this->busca}%";

                $consulta->where(fn (Builder $busca) => $busca
                    ->whereLike('titulo', $termo)
                    ->orWhereLike('descricao', $termo));
            })
            ->when($this->status !== '', fn (Builder $consulta) => $consulta->where('status', $this->status))
            ->when($this->prioridade !== '', fn (Builder $consulta) => $consulta->where('prioridade', $this->prioridade))
            ->latest()
            ->paginate(15);

        return view('livewire.lista-de-tickets', [
            'tickets' => $tickets,
            'estados' => TicketState::getStateMapping()
                ->map(fn (string $classe) => new $classe(new Ticket))
                ->values(),
            'prioridades' => Prioridade::cases(),
        ]);
    }
}