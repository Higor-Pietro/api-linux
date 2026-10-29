<?php

namespace App\Enums;

/**
 * Tipos de ação gravados na coluna "event" do activity_log.
 */
enum AcaoAuditoria: string
{
    case Criacao = 'created';
    case Alteracao = 'updated';
    case Exclusao = 'deleted';

    public function label(): string
    {
        return match ($this) {
            self::Criacao => __('Criação'),
            self::Alteracao => __('Alteração'),
            self::Exclusao => __('Exclusão'),
        };
    }

    /**
     * Cor do badge (Flux).
     */
    public function cor(): string
    {
        return match ($this) {
            self::Criacao => 'green',
            self::Alteracao => 'amber',
            self::Exclusao => 'red',
        };
    }
}