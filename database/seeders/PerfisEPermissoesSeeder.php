<?php

namespace Database\Seeders;

use App\Enums\Perfil;
use App\Enums\Permissao;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PerfisEPermissoesSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (Permissao::cases() as $permissao) {
            Permission::firstOrCreate(['name' => $permissao->value]);
        }

        $admin = Role::firstOrCreate(['name' => Perfil::Admin->value]);
        $operador = Role::firstOrCreate(['name' => Perfil::Operador->value]);

        $admin->syncPermissions([
            Permissao::GerenciarUsuarios->value,
            Permissao::GerenciarTickets->value,
            Permissao::CriarTickets->value,
            Permissao::AlterarStatusTickets->value,
            Permissao::ExcluirTickets->value,
            Permissao::EditarTodosTickets->value,
            Permissao::AlterarPrioridadeTickets->value,
            Permissao::AlterarNomeTickets->value,
            Permissao::AlterarResponsavelTickets->value,
            Permissao::VisualizarLogs->value,

        ]);

        $operador->syncPermissions([
            Permissao::GerenciarTickets->value,
            Permissao::AlterarPrioridadeTickets->value,
        ]);
    }
}