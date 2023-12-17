@php
    use Modules\View\Models\ElementPropertyModel;
    use Modules\View\Domains\ViewStructureComponentType;
    use Modules\View\Models\ElementModel;
@endphp
<div>
    @if(session()->has('success'))
                <x-dvui::toast :success="true" title="Post" title2="{{now()->diffForHumans()}}"
                               :label="session('success')"/>
    @endif

    @foreach($this->elements() as $element)
        @if($element->type() == ViewStructureComponentType::page_card)
            <form wire:submit="save">
                {{--                <x-dvui::card :attr="$element->properties->pluck('value', 'name')->all()">--}}
                <x-lte::card>
                    <x-lte::card.header>
                        <div class="flex grow justify-between pb-2">
                            <div class="grow flex ml-2">
                                <span class="font-bold my-auto text-gray-700">{{$element->properties->pluck('value', 'name')->get('title')}}</span>
                            </div>
                            <div class="flex justify-end my-auto">
                                <a href="{{route('builder.page', $page->id)}}"
                                   class="bg-blue-400 hover:bg-blue-500 text-white rounded-l px-2 py-1">
                                    <i class="fas fa-cogs"></i>
                                    builder
                                </a>
                                <x-dvui::button @class(["flex space-x-2", "rounded-r" => !$model->id,"border-gray-100"]) info
                                                sm
                                                wire:click="updateStructureCache">
                                    <i class="fas fa-sync my-auto" wire:loading.class="animate-spin"
                                       wire:target="updateStructureCache"></i>
                                    <span class="my-auto">cache</span>
                                </x-dvui::button>
                                @if($model->id)
                                    <a href="{{route($page->route)}}" wire:navigate
                                       class="bg-blue-500 hover:bg-blue-600 text-white rounded-r px-2 py-1">
                                        <i class="fas fa-plus"></i>
                                    </a>
                                @endif
                            </div>
                        </div>

                    </x-lte::card.header>
                    <x-lte::card.body>
                        @foreach($element->allChildren as $child)
                            <x-view::elements :child="$child" :model="$model"/>
                        @endforeach
                    </x-lte::card.body>
                    <x-lte::card.footer>
                        <x-dvui::button type="submit" info rounded label="Salvar"/>
                    </x-lte::card.footer>
                </x-lte::card>
                {{--                </x-dvui::card>--}}
            </form>
        @endif
    @endforeach
</div>
