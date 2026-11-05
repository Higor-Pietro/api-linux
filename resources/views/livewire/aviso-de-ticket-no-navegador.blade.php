<div wire:poll.10s.keep-alive="verificar" class="hidden">
    @script
    <script>
        const podeNotificar = () => window.isSecureContext && 'Notification' in window;

        document.addEventListener('click', () => {
            if (podeNotificar() && Notification.permission === 'default') {
                Notification.requestPermission();
            }
        }, { once: true });

        $wire.on('nova-notificacao', ({ id, mensagem }) => {
            if (podeNotificar() && Notification.permission === 'granted') {
                new Notification('Novo ticket', { body: mensagem, tag: id });
            }
        });
    </script>
    @endscript
</div>