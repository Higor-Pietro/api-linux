<?php

namespace App\Enums;

enum Permissao: string
{
    case GerenciarUsuarios = 'usuarios.gerenciar';
    case GerenciarTickets = 'tickets.gerenciar';
    case AlterarStatusTickets = 'tickets.alterar-status';
    case ExcluirTickets = 'tickets.excluir';
    case CriarTickets = 'tickets.criar';
    case EditarTodosTickets = 'tickets.editar-todos';
    case AlterarPrioridadeTickets = 'tickets.alterar-prioridade';
    case AlterarNomeTickets = 'tickets.alterar-nome';
    case AlterarResponsavelTickets = 'tickets.alterar-responsavel';
    case VisualizarLogs = 'logs.visualizar';
    case VisualizarLoges = 'logs.viesualizar';
    
}