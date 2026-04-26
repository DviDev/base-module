@props(['actions'])
@php
    use Modules\Permission\Enums\Actions;
    use Modules\Permission\Models\PermissionActionModel;


    /**@var PermissionActionModel $create*/
    $create = $actions->get(Actions::create->name);
    /**@var PermissionActionModel $delete*/
    $delete = $actions->get(Actions::delete->name);
@endphp
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
