<?php

namespace App\Livewire;

use App\Enums\Permissao;
use App\Models\Ticket;
use App\States\Ticket\Aberto;
use App\States\Ticket\EmAndamento;
use App\States\Ticket\Resolvido;
use App\States\Ticket\Fechado;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class ContadoresDeTickets extends Component
{
    public function render(): View
    {
        Gate::authorize('viewAny', Ticket::class);

        $usuario = auth()->user();

        $totais = Ticket::query()
            ->unless(
                $usuario->checkPermissionTo(Permissao::EditarTodosTickets->value),
                fn (Builder $consulta) => $consulta->where('responsavel_id', $usuario->id),
            )
            ->toBase()
            ->select('status')
            ->selectRaw('count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $contadores = collect([Aberto::class, EmAndamento::class, Resolvido::class, Fechado::class])
            ->map(fn (string $classe) => [
                'chave' => $classe::$name,
                'rotulo' => (new $classe(new Ticket))->label(),
                'total' => (int) ($totais[$classe::$name] ?? 0),
            ]);

        return view('livewire.contadores-de-tickets', [
            'contadores' => $contadores,
        ]);
    }
}