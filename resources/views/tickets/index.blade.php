<x-layouts::app :title="__('Tickets')">
    <div class="flex items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Tickets') }}</flux:heading>
            <flux:text class="mt-2">{{ __('Lista de tickets.') }}</flux:text>
        </div>

        @can('create', App\Models\Ticket::class)
            <flux:button variant="primary" icon="plus" :href="route('tickets.create')">
                {{ __('Novo ticket') }}
            </flux:button>
        @endcan
    </div>

    @if (session('status'))
        <flux:callout variant="success" icon="check-circle" class="mt-6" :heading="session('status')" />
    @endif

    <livewire:lista-de-tickets />
</x-layouts::app>