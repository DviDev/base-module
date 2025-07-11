@php
    use Modules\View\Models\ElementModel;
    use Modules\View\Models\ElementPropertyModel;
    use Modules\View\Domains\ViewStructureComponentType;
@endphp
<x-dvui::card>
    <x-slot:heading class="flex">
        <div class="grow text-lg text-gray-400">{{$page->name}}</div>
        <a href="{{route('builder.page', $page->id)}}" class="bg-blue-600 py-1 px-2 rounded text-gray-300">
            builder
        </a>
    </x-slot:heading>

    <div class="text-gray-400">
        <form wire:submit="save">
            <div class="space-y-3">
                @php
                    /**@var ElementModel $row*/
                    /**@var ElementModel $columnD*/
                @endphp
                @foreach($this->elements() as $row)
                    <div class="flex">
                        @foreach($row->columns as $columnD)
                            @php
                                $component_ = $columnD->components->first();
                            @endphp

                            <div class="p-1 grow" wire:key="{{$columnD->id}}">
                                @php
                                    $collection = collect($component_->properties);
                                    $properties = [];
                                    if (count($component_->properties) > 0) {
                                        $properties = $collection->pluck('value', 'name')->all();
                                        $properties['value'] = $model->{$properties['name']};
                                    }
                                    $attributes_ = $collection
                                        ->map(fn(ElementPropertyModel $p) => $p->name.'="'.$p->value.'"')
                                        ->join(' ');
                                @endphp
                                @if($component_->type->enum() == ViewStructureComponentType::text && isset($properties['name']) && str($properties['name'])->contains('image_path'))
                                    <x-dvui::form.fileinput :label="$properties['label']" :attr="$properties"/>
                                @elseif($component_->type->enum() == ViewStructureComponentType::text)
                                    @php
                                        $input = "dvui::form.input";
                                    @endphp
                                    @if(!isset($properties['id']))
                                        <x-dvui::form.input :attr="$properties"/>
                                    @else
                                        <x-dvui::form.input :attr="$properties"
                                                            wire:model="model.{{$properties['id']}}"/>
                                    @endif
                                @endif
                                @if($component_->type->enum() == ViewStructureComponentType::combo)
                                    @php
                                        $combo = "select";
                                    @endphp
                                    <x-dvui::form.select :label="$properties['label']"
                                                         wire:model="model.{{$properties['id']}}">
                                        @foreach($this->getReferencedTableData($component_) as $item)
                                            <x-dvui::form.select.item :value="$item->id"
                                                                      :selected="$model->{$properties['id']} == $item->id"
                                            >
                                                {{$item->value}}
                                            </x-dvui::form.select.item>
                                        @endforeach
                                    </x-dvui::form.select>
                                @endif

                                @if($component_->type->enum() == ViewStructureComponentType::text_multiline)
                                    <x-dvui::form.textarea :attrs="$properties"
                                                           wire:model="model.{{$properties['id']}}"/>
                                @endif
                                @if($component_->type->enum() == ViewStructureComponentType::number)
                                    <x-dvui::form.input label="{{$properties['label']}}" type="number"
                                                        wire:model="model.{{$properties['id']}}"/>
                                @endif
                                @if($component_->type->enum() == ViewStructureComponentType::html)
                                    <!-- component html -->
                                    <x-lte::form.summernote :attr="$properties"/>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
            <x-slot:footer>
                <x-dvui::button type="submit" label="Salvar"/>
            </x-slot:footer>
        </form>
    </div>
</x-dvui::card>
