<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;

class AuditoriaController extends Controller
{
    /**
     * Lista os logs de auditoria, do mais recente para o mais antigo.
     */
    public function index(): View
    {
        Gate::authorize('viewAny', Activity::class);

        $logs = Activity::query()
            ->with('causer')
            ->latest('id')
            ->paginate(20);

        return view('auditoria.index', [
            'logs' => $logs,
        ]);
    }
}