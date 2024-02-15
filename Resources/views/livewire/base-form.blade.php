@php
    use Modules\Base\Entities\Actions\Actions;
    use Modules\Base\Entities\Actions\Builder;
    use Modules\View\Domains\ViewStructureComponentType;
    use Modules\Project\Models\ProjectModuleEntityDBModel;
    use Modules\Project\Models\ProjectModuleEntityActionModel;
    use Modules\App\Entities\User\UserType;
@endphp
<div>
    @if(session()->has('success'))
        <x-dvui::toast :success="true" title="Post" title2="{{now()->diffForHumans()}}" :label="session('success')"/>
    @endif

    @php
        /**@var \Modules\View\Models\ElementModel $element*/
    @endphp
    @foreach($this->elements() as $element)
        @if($element->type() == ViewStructureComponentType::page_card)
            <form wire:submit="save">
                {{--                <x-dvui::card :attr="$element->properties->pluck('value', 'name')->all()">--}}
                <x-lte::card>
                    <x-lte::card.header>
                        <div class="flex grow justify-between pb-2">
                            <div class="grow flex ml-2">
                                <span class="font-bold my-auto text-gray-700">
                                    {{$element->properties->pluck('value', 'name')->get('title')}}
                                </span>
                            </div>
                            @if(Builder::can()::builder(Actions::view) || config('app.env') == 'local')
                                <div class="flex justify-end my-auto">
                                    {{--<a href="{{route('builder.page', $page->id)}}"
                                       class="bg-blue-400 hover:bg-blue-500 text-white rounded-l px-2 py-1">
                                        <i class="fas fa-cogs"></i>
                                        builder
                                    </a>--}}
                                    @if(auth()->user()->type->enum() == UserType::DEVELOPER)
                                        <a href="{{route('builder.page', $page->id)}}"
                                           class="bg-gray-100 text-blue-500 hover:text-blue-700 border border-gray-200 rounded-l px-2 py-1">
                                            <i class="fas fa-cogs"></i>
                                            builder
                                        </a>
                                    @endif
                                    <div
                                        @class([
                                            "bg-gray-100 border border-gray-200",
                                            "border-l-0" => auth()->user()->type->enum() == UserType::DEVELOPER,
                                            "rounded-l" => !auth()->user()->type->enum() == UserType::DEVELOPER,
                                             "text-blue-500 hover:text-blue-700 px-2 py-1 cursor-pointer",
                                            "flex space-x-2", "rounded-r" => !$model->id,
                                        ]) wire:click="updateStructureCache">
                                        <i class="fas fa-sync my-auto" wire:loading.class="animate-spin"
                                           wire:target="updateStructureCache"></i>
                                        <span class="my-auto">cache</span>
                                    </div>
                                    @if($model->id)
                                        <a href="{{route($page->route)}}" wire:navigate title="{{__('base.new')}}"
                                           class="bg-blue-500 hover:bg-blue-600 text-white rounded-r px-2 py-1">
                                            <x-dvui::icon.plus/>
                                        </a>
                                    @endif
                                </div>
                            @endcan()

                        </div>

                    </x-lte::card.header>
                    <x-lte::card.body>
                        @foreach($element->allChildren as $child)
                            <x-view::elements :child="$child" :model="$model"/>
                            {{--                            <livewire:view::form.elements :child="$child" :model="$model" wire:key="{{$child->id}}"/>--}}
                        @endforeach
                    </x-lte::card.body>
                    @php
                        /**@var ProjectModuleEntityDBModel $entity*/
                        $entity = $element->structure()->with('page.entity')->first()->page->entity;
                        /**@var ProjectModuleEntityActionModel $save*/
                        $save = $entity->actions()->where('name', 'save')->get()->first();
                        $delete = $entity->actions()->where('name', 'delete')->get()->first();
                    @endphp
                    <x-lte::card.footer>
                        <div class="flex justify-between">
                            @if($save->checkConditions())
                                <x-dvui::button type="submit" info rounded label="Salvar"/>
                            @endif
                            @if($delete->checkConditions())
                                <x-dvui::button danger rounded label="Remover" confirm action="delete"/>
                            @endif
                        </div>
                    </x-lte::card.footer>
                </x-lte::card>
                {{--                </x-dvui::card>--}}
            </form>
        @endif
    @endforeach
</div>
