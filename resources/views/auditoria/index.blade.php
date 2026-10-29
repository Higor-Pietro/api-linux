<x-layouts::app :title="__('Logs')">
    <div>
        <flux:heading size="xl">{{ __('Logs') }}</flux:heading>
        <flux:text class="mt-2">{{ __('Histórico de alterações em usuários e tickets.') }}</flux:text>
    </div>

        <form method="GET" action="{{ route('auditoria.index') }}" class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4 lg:items-end">
        <flux:select name="usuario_id" :label="__('Usuário')" :placeholder="__('Todos')">
            @foreach ($usuarios as $usuario)
                <flux:select.option :value="$usuario->id" :selected="(string) ($filtros['usuario_id'] ?? '') === (string) $usuario->id">
                    {{ $usuario->name }}
                </flux:select.option>
            @endforeach
        </flux:select>

        <flux:select name="acao" :label="__('Ação')" :placeholder="__('Todas')">
            @foreach ($acoes as $acao)
                <flux:select.option :value="$acao->value" :selected="($filtros['acao'] ?? null) === $acao->value">
                    {{ $acao->label() }}
                </flux:select.option>
            @endforeach
        </flux:select>

        <flux:input type="date" name="data_inicio" :label="__('De')" :value="$filtros['data_inicio'] ?? ''" />

        <flux:input type="date" name="data_fim" :label="__('Até')" :value="$filtros['data_fim'] ?? ''" />

        <div class="flex gap-2 lg:col-span-4">
            <flux:button variant="primary" type="submit" icon="funnel">{{ __('Filtrar') }}</flux:button>
            <flux:button :href="route('auditoria.index')" variant="ghost">{{ __('Limpar') }}</flux:button>
        </div>
    </form>

    <flux:table class="mt-6">
        <flux:table.columns>
            <flux:table.column>{{ __('Data') }}</flux:table.column>
            <flux:table.column>{{ __('Usuário') }}</flux:table.column>
            <flux:table.column>{{ __('Ação') }}</flux:table.column>
            <flux:table.column>{{ __('Registro') }}</flux:table.column>
            <flux:table.column>{{ __('Campos') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($logs as $log)
                @php
                    $acao = App\Enums\AcaoAuditoria::tryFrom($log->event);

                    $registro = match ($log->subject_type) {
                        App\Models\Ticket::class => __('Ticket'),
                        App\Models\User::class => __('Usuário'),
                        default => class_basename($log->subject_type),
                    };

                    // created/updated têm "attributes"; deleted só tem "old".
                    $campos = array_keys(
                        $log->attribute_changes?->get('attributes')
                            ?? $log->attribute_changes?->get('old')
                            ?? []
                    );
                    $antigo = $log->attribute_changes?->get('old') ?? [];

                    $nome = match (true) {
                        $log->subject instanceof App\Models\Ticket => $log->subject->titulo,
                        $log->subject instanceof App\Models\User => $log->subject->name,
                        default => $antigo['titulo'] ?? $antigo['name'] ?? null,
                    };
                @endphp

                <flux:table.row :key="$log->id">
                    <flux:table.cell>{{ $log->created_at->format('d/m/Y H:i') }}</flux:table.cell>
                    <flux:table.cell>{{ $log->causer?->name ?? __('Sistema') }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" :color="$acao?->cor() ?? 'zinc'">
                            {{ $acao?->label() ?? $log->event }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        @if ($nome)
                            <flux:tooltip :content="$nome" position="top">
                                <span class="cursor-help underline decoration-dotted underline-offset-4">
                                    {{ $registro }} #{{ $log->subject_id }}
                                </span>
                            </flux:tooltip>
                        @else
                            {{ $registro }} #{{ $log->subject_id }}
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>{{ implode(', ', $campos) }}</flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5">{{ __('Nenhum registro de auditoria.') }}</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <div class="mt-4">
        {{ $logs->links() }}
    </div>
</x-layouts::app>