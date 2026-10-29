<x-layouts::app :title="__('Detalhe do log')">
    <div class="flex items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Log') }} #{{ $log->id }}</flux:heading>
            <flux:text class="mt-2">{{ $log->created_at->format('d/m/Y H:i:s') }}</flux:text>
        </div>

        <flux:button :href="route('auditoria.index')" variant="ghost" icon="arrow-left">
            {{ __('Voltar') }}
        </flux:button>
    </div>

    <div class="mt-6 grid max-w-xl grid-cols-[auto_1fr] gap-x-6 gap-y-3">
        <flux:text>{{ __('Usuário') }}</flux:text>
        <flux:text variant="strong">{{ $log->causer?->name ?? __('Sistema') }}</flux:text>

        <flux:text>{{ __('Ação') }}</flux:text>
        <div>
            <flux:badge size="sm" :color="$acao?->cor() ?? 'zinc'">
                {{ $acao?->label() ?? $log->event }}
            </flux:badge>
        </div>

        <flux:text>{{ __('Registro') }}</flux:text>
        <flux:text variant="strong">{{ class_basename($log->subject_type) }} #{{ $log->subject_id }}</flux:text>
    </div>

    <flux:table class="mt-8">
        <flux:table.columns>
            <flux:table.column>{{ __('Campo') }}</flux:table.column>
            <flux:table.column>{{ __('Valor anterior') }}</flux:table.column>
            <flux:table.column>{{ __('Valor novo') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($alteracoes as $alteracao)
                <flux:table.row>
                    <flux:table.cell variant="strong">{{ $alteracao['campo'] }}</flux:table.cell>
                    <flux:table.cell class="whitespace-normal text-red-600 dark:text-red-400">{{ $alteracao['anterior'] }}</flux:table.cell>
                    <flux:table.cell class="whitespace-normal text-green-600 dark:text-green-400">{{ $alteracao['novo'] }}</flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="3">{{ __('Nenhum campo registrado neste log.') }}</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</x-layouts::app>