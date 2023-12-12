<?php

namespace Modules\Base\View\Components\Form;

use Illuminate\View\Component;
use Illuminate\View\View;

class BaseFormBladeComponent extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the view/contents that represent the component.
     */
    public function render(): View|string
    {
        return view('base::components.form/baseformbladecomponent');
    }
}
