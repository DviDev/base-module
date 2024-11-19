@pushonce('livewire_styles')
    @livewireStyles
@endpushonce
@pushonce('livewire_scripts')
    @livewireScripts
    @livewireScriptConfig
@endpushonce
<x-lte::layout.v1.page title="Notificação">
    <livewire:base::notification.notification-view :notification="$notification"/>
</x-lte::layout.v1.page>
