<?php

namespace App\Http\Controllers;

use App\Enums\AcaoAuditoria;
use App\Http\Requests\FiltrarAuditoriaRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;
use App\Enums\Perfil;

class AuditoriaController extends Controller
{
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
}