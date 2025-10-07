<?php

namespace App\View\Components\Admin\FormElements;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class AdvertiserSelect extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.admin.form-elements.advertiser-select');
    }
}
