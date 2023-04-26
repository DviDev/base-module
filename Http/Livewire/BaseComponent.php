<?php

namespace Modules\Base\Http\Livewire;

use Livewire\Component;
use Modules\Base\Models\BaseModel;
use Modules\LaravelProgram\Commands\DviRequestMakeCommand;
use Modules\LaravelProgram\Domains\ViewPage;
use Modules\LaravelProgram\Models\ViewPageModel;
use Modules\ViewStructure\Models\ViewStructureColumnComponentModel;
use Modules\ViewStructure\Models\ViewStructureRowModel;

abstract class BaseComponent extends Component
{
    public BaseModel $model;
    public ViewPageModel $page;

    protected $visible_rows;

    public function render()
    {
        $this->getRows();
    }

    public function getRows()
    {
        $this->page = (new ViewPage)->repository()->page(1);

        $fn = function() {
            $visible_rows = [];
            /**@var ViewStructureRowModel $row */
            foreach ($this->page->rows as $row) {
                $contain = false;
                foreach ($row->columns as $column) {
                    /**@var ViewStructureColumnComponentModel $component */
                    $component = $column->components->first();
                    $contain = collect([
                        'id',
                        'created_at',
                        'updated_at',
                        'deleted_at'
                    ])->some($component->attribute->name);
                    if ($contain) {
                        break;
                    }

                }
                if ($contain) {
                    continue;
                }
                $visible_rows[$row->id] = $row;
            }

            return $visible_rows;
        };

        $this->visible_rows = $this->visible_rows ?: $fn();

        return $this->visible_rows;
    }

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
