<div wire:poll.15s class="grid auto-rows-min gap-4 md:grid-cols-3">
    @foreach ($contadores as $contador)
        <div
            wire:key="contador-{{ $contador['chave'] }}"
            class="rounded-xl border border-neutral-200 p-6 dark:border-neutral-700"
        >
            <flux:text>{{ $contador['rotulo'] }}</flux:text>
            <flux:heading size="xl" class="mt-2">{{ $contador['total'] }}</flux:heading>
        </div>
    @endforeach
</div>