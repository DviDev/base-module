@pushonce('livewire_styles')
    @livewireStyles
@endpushonce
@pushonce('livewire_scripts')
    @livewireScripts
    @livewireScriptConfig
@endpushonce
<x-lte::layout.v1.page title="Notificação">
    <x-lte::card card_id="notificação" class="shadow-none border-0">
        <x-lte::card.body>
            <livewire:base::notification.notification-view :notification="$notification"/>
        </x-lte::card.body>
        <x-lte::card.footer>
            <a href="{{url()->previous()}}" class="btn btn-default">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </x-lte::card.footer>
    </x-lte::card>
</x-lte::layout.v1.page>
