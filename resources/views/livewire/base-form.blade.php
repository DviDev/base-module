@php
    use Modules\Permission\Enums\Actions;
    use Modules\Base\Entities\Actions\Builder;
    use Modules\Person\Enums\UserType;
    use Modules\Permission\Models\PermissionActionModel;
    use Modules\Project\Models\ProjectModuleEntityDBModel;
    use Modules\View\Domains\ViewStructureComponentType;
    use Modules\View\Models\ViewPageStructureModel;
@endphp
<div>
    @php
        /**@var \Modules\View\Models\ElementModel $element*/
    @endphp
    @foreach($this->elements() as $element)
        @if($element->type() == ViewStructureComponentType::page_card)
            <form wire:submit="save">
                <x-lte::card>
                    <x-lte::card.header :navs="false">
                        <div class="card-header flex grow py-2 justify-between">
                            <div class="grow flex ml-2">
                                <span class="font-bold my-auto text-gray-700">
                                    {{$element->properties->pluck('value', 'name')->get('title')}}
                                </span>
                            </div>
                            @if(Builder::can()::builder(Actions::view) || config('app.env') == 'local')
                                <div class="flex justify-end my-auto border border-gray-200 rounded">
                                    @if(auth()->user()->type->enum() == UserType::DEVELOPER)
                                        <a href="{{route('builder.page', $page->uuid)}}" target="_blank"
                                           class="bg-gray-100 text-gray-600 hover:text-blue-700 rounded-l px-2 py-1">
                                            <i class="fas fa-cogs"></i>
                                            builder
                                        </a>
                                    @endif
                                    <div
                                        @class([
                                            "bg-gray-100",
                                             "text-gray-600 hover:text-blue-700 px-2 py-1 cursor-pointer",
                                            "flex space-x-2", "rounded-r" => !isset($model['id']),
                                        ])
                                        wire:click="updateStructureCache"
                                        title="{{ucfirst(__('base::cache.update cache'))}}">
                                        <x-dvui::icon.trash s4 fill class="my-auto font-bold"
                                                            wire:loading.class="hidden"
                                                            wire:target="updateStructureCache"/>
                                        <i class="fas fa-sync my-auto"
                                           wire:loading
                                           wire:loading.class="animate-spin"
                                           wire:target="updateStructureCache"></i>
                                        <span class="my-auto">cache</span>
                                    </div>
                                    <div
                                        @class([
                                            "bg-gray-100",
                                             "text-gray-600 hover:text-blue-700 px-2 py-1 cursor-pointer",
                                            "flex space-x-2", "rounded-r" => !isset($model['id']),
                                        ]) wire:click="updateComponent" title="{{__('base::page.update page')}}">
                                        <i class="fas fa-sync my-auto" wire:loading.class="animate-spin"
                                           wire:target="updateComponent"></i>
                                        <span class="my-auto">refresh</span>
                                    </div>
                                    <div class="bg-gray-100 flex">
                                        <a href="{{route($page->entity->firstPageList()->route)}}" wire:navigate title="{{__('list')}}"
                                            @class(["text-gray-600 hover:text-blue-600 px-2 my-auto"])>
                                            <x-dvui::icon.document mini wire:loading.class="animate-spin"/>
                                        </a>
                                    </div>
                                    @isset($model['id'])
                                        <a href="{{route($page->route)}}" wire:navigate title="{{__('new')}}"
                                            @class(["bg-gray-100 text-gray-600 hover:text-blue-600 rounded-r px-2 py-1"])>
                                            <x-dvui::icon.plus/>
                                        </a>
                                    @endisset
                                </div>
                            @endcan()
                        </div>
                    </x-lte::card.header>
                    <x-lte::card.body>
                        @foreach($element->allChildren as $child)
                            <x-view::elements :child="$child" :model="$model"/>
                        @endforeach
                    </x-lte::card.body>
                    @php
                        $actions = $this->getStructure()->actions()
                            ->whereIn('name', [Actions::create, Actions::delete])
                            ->get()
                            ->keyBy('name');
                        /**@var PermissionActionModel $create*/
                        $create = $actions->get(Actions::create->name);
                        /**@var PermissionActionModel $delete*/
                        $delete = $actions->get(Actions::delete->name);
                    @endphp
                    <x-lte::card.footer>
                        <div class="flex justify-between items-center">
                            @if($create?->checkConditions())
                                <x-project::module.entity.action.conditions-link :action="$create">
                                    <x-dvui::button type="submit" info rounded label="Salvar"/>
                                </x-project::module.entity.action.conditions-link>
                            @endif
                            @if($delete?->checkConditions())
                                <x-project::module.entity.action.conditions-link :action="$delete">
                                    <x-dvui::button danger rounded label="Remover" confirm action="delete"/>
                                </x-project::module.entity.action.conditions-link>
                            @endif
                        </div>
                    </x-lte::card.footer>
                </x-lte::card>
            </form>
        @endif
    @endforeach
</div>
