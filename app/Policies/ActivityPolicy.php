<?php

namespace App\Policies;

use App\Enums\Permissao;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;

/**
 * Logs de auditoria são somente leitura: não há create, update nem delete,
 * então o Gate nega essas ações por padrão.
 */
class ActivityPolicy
{
    /**
     * Listar os logs.
     */
    public function viewAny(User $user): bool
    {
        return $this->podeVisualizarLogs($user);
    }

    /**
     * Ver o detalhe de um log.
     */
    public function view(User $user, Activity $activity): bool
    {
        return $this->podeVisualizarLogs($user);
    }

    private function podeVisualizarLogs(User $user): bool
    {
        return $user->checkPermissionTo(Permissao::VisualizarLogs->value);
    }
}