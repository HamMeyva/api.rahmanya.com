<?php

namespace App\View\Components\Admin\FormElements;

use Closure;
use App\Models\Demographic\Placement;
use Illuminate\View\Component;
use Illuminate\Contracts\View\View;

class PlacementSelect extends Component
{
    public $options;

    public function __construct()
    {
        $this->options = Placement::query()
            ->get()
            ->map(function ($item) {
                return [
                    'value' => $item->id,
                    'label' => $item->name,
                ];
            });
    }

    public function render(): View|Closure|string
    {
        return view('components.admin.form-elements.placement-select');
    }
}
