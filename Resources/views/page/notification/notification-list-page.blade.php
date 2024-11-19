@pushonce('livewire_styles')
    @livewireStyles
@endpushonce
@pushonce('livewire_scripts')
    @livewireScripts
    @livewireScriptConfig
@endpushonce
<x-lte::layout.v1.page :title="str(__('notifications'))->upper()->value()">
    <livewire:base::notification.notification-list/>
</x-lte::layout.v1.page>
