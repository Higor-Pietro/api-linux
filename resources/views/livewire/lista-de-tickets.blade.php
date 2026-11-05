<div>
    <div class="mt-6 grid gap-4 md:grid-cols-3">
        <flux:input
            wire:model.live.debounce.300ms="busca"
            :label="__('Buscar')"
            :placeholder="__('Título ou descrição')"
            icon="magnifying-glass"
        />

        <flux:select wire:model.live="status" :label="__('Status')">
            <flux:select.option value="">{{ __('Todos') }}</flux:select.option>

            @foreach ($estados as $estado)
                <flux:select.option :value="$estado->getValue()">
                    {{ $estado->label() }}
                </flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="prioridade" :label="__('Prioridade')">
            <flux:select.option value="">{{ __('Todas') }}</flux:select.option>

            @foreach ($prioridades as $opcao)
                <flux:select.option :value="$opcao->value">
                    {{ $opcao->label() }}
                </flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <flux:table class="mt-6">
        <flux:table.columns>
            <flux:table.column>{{ __('Título') }}</flux:table.column>
            <flux:table.column>{{ __('Status') }}</flux:table.column>
            <flux:table.column>{{ __('Prioridade') }}</flux:table.column>
            <flux:table.column>{{ __('Responsável') }}</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($tickets as $ticket)
                <flux:table.row :key="$ticket->id" wire:key="ticket-{{ $ticket->id }}">
                    <flux:table.cell>{{ $ticket->titulo }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" :color="$ticket->status->cor()">
                            {{ $ticket->status->label() }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>{{ $ticket->prioridade->label() }}</flux:table.cell>
                    <flux:table.cell>{{ $ticket->responsavel->name }}</flux:table.cell>
                    <flux:table.cell class="text-end">
                        <div class="flex items-center justify-end gap-2">
                            @can('update', $ticket)
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('tickets.edit', $ticket)">
                                    {{ __('Editar') }}
                                </flux:button>
                            @endcan

                            @can('delete', $ticket)
                                <form
                                    method="POST"
                                    action="{{ route('tickets.destroy', $ticket) }}"
                                    onsubmit="return confirm({{ Js::from(__('Excluir o ticket :titulo? Esta ação não pode ser desfeita.', ['titulo' => $ticket->titulo])) }})"
                                >
                                    @csrf
                                    @method('DELETE')

                                    <flux:button size="sm" variant="danger" icon="trash" type="submit">
                                        {{ __('Excluir') }}
                                    </flux:button>
                                </form>
                            @endcan
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5">{{ __('Nenhum ticket encontrado.') }}</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <div class="mt-4">
        {{ $tickets->links() }}
    </div>
</div>