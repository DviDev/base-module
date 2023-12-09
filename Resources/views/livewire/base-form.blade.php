@php
    use Modules\View\Models\ElementPropertyModel;
    use Modules\View\Domains\ViewStructureComponentType;
    use Modules\View\Models\ElementModel;
@endphp
<div>
    @if(session()->has('success'))
        {{--
                <x-dvui::toast :success="true" title="Post" title2="{{now()->diffForHumans()}}"
                               :label="session('success')"/>
        --}}
    @endif
    <div class="border flex justify-end">
        <a href="{{route('builder.page', $page->id)}}">builder</a>
    </div>
    @foreach($this->elements() as $element)
        @if($element->type() == ViewStructureComponentType::page_card)
            <form wire:submit="save">
                {{--                <x-dvui::card :attr="$element->properties->pluck('value', 'name')->all()">--}}
                <x-lte::card :attr="$element->properties->pluck('value', 'name')->all()">
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
