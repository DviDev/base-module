@pushonce('livewire_styles')
    @livewireStyles
@endpushonce
@pushonce('livewire_scripts')
    @livewireScripts
    @livewireScriptConfig
@endpushonce
@php
    $notifications = auth()->user()->notifications()->paginate(15);
@endphp
<x-lte::layout.v1.page :title="str(__('notifications'))->upper()->value()">
    <x-lte::card card_id="notifications" :header="str(__('notifications'))->ucfirst()" class="shadow-none border-0">
        <x-lte::card.body>
            <livewire:base::notification.notification-list :notifications="$notifications"
                                                           pagination-theme="tailwindcss"/>
        </x-lte::card.body>
        @if($notifications->total() > $notifications->perPage())
            <x-lte::card.footer class="flex">
                {{$notifications->links()}}
            </x-lte::card.footer>
        @endif
    </x-lte::card>

</x-lte::layout.v1.page>
