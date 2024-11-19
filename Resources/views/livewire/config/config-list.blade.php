<x-lte::card card_id="configs" class="shadow-none">
    <x-slot:header>
        <span class="card-title">Configurações</span>
    </x-slot:header>
    <x-slot name="tools">
        <a href="{{route('admin.config')}}" class="btn btn-primary btn-sm">
            <i class="fas fa-plus"></i> Cadastrar
        </a>
    </x-slot>
    <x-lte::card.body>
        <x-lte::form wire:submit="search">
            <div class="row">
                <div class="col-sm-10">
                    <x-lte::form.input txt="nome" wire:model="search" class="mb-2"/>
                </div>
                <div class="col-sm-2 mt-4 pt-2">
                    <div class="input-group">
                        <button title="Pesquisar" type="submit"
                                class="btn-flat text-white py-1.5 px-2 bg-green rounded-l-md">
                            <x-dvui::icon.arrow.path wire:loading wire:loading.class="animate-spin"
                                                     wire:target="search"/>
                            <x-dvui::icon.magnifying.glass wire:loading.class="hidden" wire:target="search"/>
                        </button>
                        <button title="Limpar filtros" wire:click.prevent="clear" type="button"
                                class="btn-flat text-white py-1.5 px-2 bg-gray-500 rounded-r-md">
                            <x-dvui::icon.arrow.path
                                wire:loading.class="animate-spin"
                                wire:target="clear"/>
                        </button>
                    </div>
                </div>
            </div>
        </x-lte::form>
        <table class="table table-hover table-sm ">
            <tr>
                <th>Nome</th>
                <th>Valor</th>
                <th style="width: 10%"></th>
            </tr>
            <tbody>
            @php
                $list = $this->list();
            @endphp
            @if($list->count() == 0)
                <tr>
                    <td colspan="2">
                        <div class="row justify-content-md-center">
                            <div class="col-6">
                                <x-dvui::alert icon="exclamation" :dismiss="true" :yellow="true"
                                               content="nenhum registro encontrado" sm/>
                            </div>
                        </div>
                    </td>
                </tr>
            @endif

            @foreach($list as $config)
                <livewire:base::config.config-list-item :config="$config" wire:key="{{$config->id}}"/>
            @endforeach
            </tbody>
        </table>
    </x-lte::card.body>
    @if($list->total() > $list->perPage())
        <x-lte::card.footer>
            {{$list->links()}}
        </x-lte::card.footer>
    @endif
</x-lte::card>
