@php
    use Illuminate\Support\Facades\URL;
@endphp

<x-lte::card card_id="config_form" class="shadow-none border-0">
    <x-lte::form wire:submit="save">
        <x-lte::card.body>
            <x-lte::page_alert :toastr="true" :only_toastr="true"/>
            <x-lte::form.input txt="name" label="Nome" wire:model="name" :disabled="$config->default"/>
            <x-lte::form.input txt="value" label="Valor" wire:model="config.value"/>
            <x-lte::form.summernote id="description" label="Descrição" wire:model="config.description"/>
        </x-lte::card.body>
        <x-lte::card.footer class="flex">
            <div class="flex space-x-2 grow">
                <a href="{{$config->id ? URL::previous() : route('admin.configs')}}" class="btn btn-default"
                   title="voltar">
                    <i class="fas fa-arrow-left"></i> Voltar
                </a>
                <x-lte::button label="Salvar" icon="save" :success="true"/>
            </div>
            @if($config->id)
                <div class="flex items-end text-sm text-gray-400">
                    <span>atualizado {{$config->updated_at->longRelativeToNowDiffForHumans()}} por {{$config->user->name}}</span>
                </div>
            @endif
        </x-lte::card.footer>
    </x-lte::form>
</x-lte::card>
