<div wire:poll.10s class="px-2">
    <flux:dropdown position="top" align="start">
        <flux:button variant="ghost" icon="bell" class="w-full justify-start">
            {{ __('Notificações') }}

            @if ($total > 0)
                <flux:badge size="sm" color="red">{{ $total }}</flux:badge>
            @endif
        </flux:button>

        <flux:menu>
            @forelse ($notificacoes as $notificacao)
                <flux:menu.item
                    wire:key="notificacao-{{ $notificacao->id }}"
                    wire:click="abrir('{{ $notificacao->id }}')"
                >
                    {{ $notificacao->data['mensagem'] }}
                </flux:menu.item>
            @empty
                <flux:menu.item disabled>{{ __('Nenhuma notificação nova') }}</flux:menu.item>
            @endforelse
        </flux:menu>
    </flux:dropdown>
</div>