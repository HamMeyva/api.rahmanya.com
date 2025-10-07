<?php

namespace App\View\Components\Admin\FormElements;

use App\Models\Admin;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class AdminSelect extends Component
{
    public $options;
    public function __construct()
    {
        $this->options = Admin::query()
            ->get()
            ->map(function ($item) {
                return [
                    'value' => $item->id,
                    'label' => $item->full_name,
                ];
            });
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.admin.form-elements.admin-select');
    }
}
