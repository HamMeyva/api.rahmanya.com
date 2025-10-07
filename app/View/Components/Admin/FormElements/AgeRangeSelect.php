<?php

namespace App\View\Components\Admin\FormElements;

use App\Models\Demographic\AgeRange;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class AgeRangeSelect extends Component
{
    public $options;

    public function __construct()
    {
        $this->options = AgeRange::query()
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
        return view('components.admin.form-elements.age-range-select');
    }
}
