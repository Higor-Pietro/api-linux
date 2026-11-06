<x-layouts::app :title="__('Dashboard')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="rounded-xl border border-neutral-200 p-6 dark:border-neutral-700">
            <flux:heading size="lg">
                {{ __('Bem vindo, :nome', ['nome' => auth()->user()->name]) }}
            </flux:heading>

            <div class="mt-2 flex items-center gap-2">
                <flux:text>{{ __('Perfil:') }}</flux:text>
                <flux:badge>
                    {{ auth()->user()->getRoleNames()->implode(', ') ?: __('Sem perfil') }}
                </flux:badge>
            </div>
            
            @can('viewAny', App\Models\User::class)
                <div class="mt-4">
                    <flux:button
                        variant="primary"
                        icon="users"
                        :href="route('usuarios.index')"
                        wire:navigate
                    >
                        {{ __('Gerenciar usuários') }}
                    </flux:button>
                </div>
            @endcan

        </div>
        @can('viewAny', App\Models\Ticket::class)
            <livewire:contadores-de-tickets />
        @endcan
        <div class="relative h-full flex-1 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
            <x-placeholder-pattern class="absolute inset-0 size-full stroke-gray-900/20 dark:stroke-neutral-100/20" />
        </div>
    </div>
</x-layouts::app>
