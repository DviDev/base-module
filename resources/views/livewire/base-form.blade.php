@php
    use Modules\Permission\Enums\Actions;
    use Modules\Base\Entities\Actions\Builder;
    use Modules\Person\Enums\UserType;
    use Modules\Permission\Models\PermissionActionModel;
    use Modules\Schema\Models\ModuleEntityDBModel;
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
                <x-base::pages.builder_bar :element="$element" :page="$page"/>

                <div>
                    @foreach($element->allChildren as $child)
                        <x-view::elements
                            :child="$child" :model="$model"
                            :extra="collect(['editingAttribute' => $editingAttribute])"
                        />
                    @endforeach
                </div>

                @php
                    $actions = $this->getFormActions();
                @endphp
                <x-base::pages.form.form_actions :actions="$actions"/>
            </form>
        @endif
    @endforeach
</div>
