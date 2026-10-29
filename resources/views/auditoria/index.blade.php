<x-layouts::app :title="__('Logs')">
    <div>
        <flux:heading size="xl">{{ __('Logs') }}</flux:heading>
        <flux:text class="mt-2">{{ __('Histórico de alterações em usuários e tickets.') }}</flux:text>
    </div>

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
                @endphp

                <flux:table.row :key="$log->id">
                    <flux:table.cell>{{ $log->created_at->format('d/m/Y H:i') }}</flux:table.cell>
                    <flux:table.cell>{{ $log->causer?->name ?? __('Sistema') }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" :color="$acao?->cor() ?? 'zinc'">
                            {{ $acao?->label() ?? $log->event }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>{{ $registro }} #{{ $log->subject_id }}</flux:table.cell>
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