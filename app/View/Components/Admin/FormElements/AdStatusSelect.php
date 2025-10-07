<?php

namespace App\View\Components\Admin\FormElements;

use App\Models\Ad\Ad;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class AdStatusSelect extends Component
{
    public $options;

    public function __construct()
    {
        foreach(Ad::$statuses as $key => $label) {
            $this->options[] = [
                'value' => $key,
                'label' => $label
            ];
        }
    }

    public function render(): View|Closure|string
    {
        return view('components.admin.form-elements.ad-status-select');
    }
}
