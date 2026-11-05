<?php

namespace App\Http\Controllers;

use App\Enums\Prioridade;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class TicketController extends Controller
{
    public function __construct(private TicketService $ticketService) {}

    /**
     * Lista os tickets.
     */
    public function index(): View
    {
        Gate::authorize('viewAny', Ticket::class);

        return view('tickets.index');
    }

    /**
     * Exibe o formulário de criação.
     */
    public function create(): View
    {
        Gate::authorize('create', Ticket::class);

        return view('tickets.create', [
            'prioridades' => Prioridade::cases(),
            'responsaveis' => $this->responsaveis(),
            'statusInicial' => (new Ticket)->status,
        ]);
    }

    /**
     * Grava um novo ticket.
     */
    public function store(StoreTicketRequest $request): RedirectResponse
    {
        $this->ticketService->criar($request->validated());

        return redirect()
            ->route('tickets.index')
            ->with('status', __('Ticket criado com sucesso.'));
    }

    /**
     * Exibe o formulário de edição.
     */
    public function edit(Ticket $ticket): View
    {
        Gate::authorize('update', $ticket);

        return view('tickets.edit', [
            'ticket' => $ticket,
            'prioridades' => Prioridade::cases(),
            'responsaveis' => $this->responsaveis(),
            'estados' => [
                $ticket->status,
                ...$ticket->status->transitionableStateInstances(),
            ],
        ]);
    }

    /**
     * Grava as alterações de um ticket.
     */
    public function update(UpdateTicketRequest $request, Ticket $ticket): RedirectResponse
    {
        $this->ticketService->atualizar($ticket, $request->validated());

        return redirect()
            ->route('tickets.index')
            ->with('status', __('Ticket atualizado com sucesso.'));
    }

    /**
     * Exclui um ticket.
     */
    public function destroy(Ticket $ticket): RedirectResponse
    {
        Gate::authorize('delete', $ticket);

        $this->ticketService->excluir($ticket);

        return redirect()
            ->route('tickets.index')
            ->with('status', __('Ticket excluído com sucesso.'));
    }

    /**
     * Usuários que podem ser escolhidos como responsáveis.
     *
     * @return Collection<int, User>
     */
    private function responsaveis(): Collection
    {
        return User::query()->orderBy('name')->get(['id', 'name']);
    }
}