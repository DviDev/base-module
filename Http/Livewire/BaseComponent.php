<?php

namespace Modules\Base\Http\Livewire;

use Livewire\Component;
use Modules\Base\Models\BaseModel;
use Modules\LaravelProgram\Commands\DviRequestMakeCommand;
use Modules\LaravelProgram\Models\ViewPageModel;
use Modules\ViewStructure\Models\ViewStructureColumnComponentModel;

abstract class BaseComponent extends Component
{
    public BaseModel $model;
    public ViewPageModel $page;

    protected $visible_rows = [];

    public function getRules()
    {
        return (new DviRequestMakeCommand)->getRules($this->model->getTable(), 'save', $this->model);
    }

    public function save()
    {
        $this->validate();
        $this->model->save();
        session()->flash('success', __('the data has been saved'));

        return redirect()->to($this->getCurrentRoute());
    }

    public function getReferencedTableData(ViewStructureColumnComponentModel $component): array
    {
        return \DB::table($component->attribute->referenced_table_name)
            ->get(['id', 'name as value'])
            ->all();
    }
}
