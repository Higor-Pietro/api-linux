<?php

namespace App\Http\Controllers;

use App\Enums\AcaoAuditoria;
use App\Http\Requests\FiltrarAuditoriaRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;
use App\Enums\Perfil;
use App\Enums\Prioridade;
use App\Models\Ticket;
use App\States\Ticket\TicketState;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

class AuditoriaController extends Controller
{

    /**
     * Nome de exibição de cada coluna registrada no log.
     */
    private const ROTULOS = [
        'titulo' => 'Título',
        'descricao' => 'Descrição',
        'status' => 'Status',
        'prioridade' => 'Prioridade',
        'responsavel_id' => 'Responsável',
        'name' => 'Nome',
        'email' => 'E-mail',
        'observacoes' => 'Observações',
    ];


    /**
     * Lista os logs de auditoria, do mais recente para o mais antigo,
     * aplicando apenas os filtros preenchidos.
     */
    public function index(FiltrarAuditoriaRequest $request): View
    {
        $filtros = $request->validated();

        $logs = Activity::query()
            ->with(['causer', 'subject'])
            ->when($filtros['usuario_id'] ?? null, fn (Builder $query, $usuarioId) => $query
                ->where('causer_type', User::class)
                ->where('causer_id', $usuarioId))
            ->when($filtros['acao'] ?? null, fn (Builder $query, $acao) => $query->forEvent($acao))
            ->when($filtros['data_inicio'] ?? null, fn (Builder $query, $data) => $query->whereDate('created_at', '>=', $data))
            ->when($filtros['data_fim'] ?? null, fn (Builder $query, $data) => $query->whereDate('created_at', '<=', $data))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('auditoria.index', [
            'logs' => $logs,
            'filtros' => $filtros,
            'usuarios' => User::role(Perfil::Admin->value)->orderBy('name')->get(['id', 'name']),
            'acoes' => AcaoAuditoria::cases(),
        ]);
    }

        /**
     * Detalhe de um log: o que mudou, com valor anterior e novo.
     */
    public function show(Activity $activity): View
    {
        Gate::authorize('view', $activity);

        $activity->load(['causer', 'subject']);

        return view('auditoria.show', [
            'log' => $activity,
            'acao' => AcaoAuditoria::tryFrom($activity->event),
            'alteracoes' => $this->alteracoes($activity),
        ]);
    }

    /**
     * Linhas "campo | anterior | novo" já formatadas para exibição.
     *
     * @return list<array{campo: string, anterior: string, novo: string}>
     */
    private function alteracoes(Activity $activity): array
    {
        $antigos = $activity->attribute_changes?->get('old') ?? [];
        $novos = $activity->attribute_changes?->get('attributes') ?? [];

        // Nomes dos responsáveis citados no log, numa única consulta.
        $responsaveis = User::query()
            ->whereIn('id', array_filter([$antigos['responsavel_id'] ?? null, $novos['responsavel_id'] ?? null]))
            ->pluck('name', 'id');

        return collect(array_keys($novos + $antigos))
            ->map(fn (string $campo) => [
                'campo' => __(self::ROTULOS[$campo] ?? $campo),
                'anterior' => $this->formatar($campo, $antigos[$campo] ?? null, $responsaveis),
                'novo' => $this->formatar($campo, $novos[$campo] ?? null, $responsaveis),
            ])
            ->all();
    }

    /**
     * Converte o valor cru gravado no log em texto legível.
     *
     * @param  Collection<int, string>  $responsaveis
     */
    private function formatar(string $campo, mixed $valor, Collection $responsaveis): string
    {
        if ($valor === null || $valor === '') {
            return '—';
        }

        return match ($campo) {
            'prioridade' => Prioridade::tryFrom($valor)?->label() ?? $valor,
            'status' => $this->rotuloStatus($valor),
            'responsavel_id' => $responsaveis[$valor] ?? '#'.$valor,
            default => (string) $valor,
        };
    }

    /**
     * "em_andamento" → "Em andamento", usando a classe de estado correspondente.
     */
    private function rotuloStatus(string $valor): string
    {
        $classe = TicketState::resolveStateClass($valor);

        return is_subclass_of($classe, TicketState::class)
            ? TicketState::make($valor, new Ticket)->label()
            : $valor;
    }
}