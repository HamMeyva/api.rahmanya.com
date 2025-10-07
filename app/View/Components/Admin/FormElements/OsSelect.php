<?php

namespace App\View\Components\Admin\FormElements;

use App\Models\Demographic\Os;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class OsSelect extends Component
{
    public $options;

    public function __construct()
    {
        $this->options = Os::query()
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
        return view('components.admin.form-elements.os-select');
    }
}
