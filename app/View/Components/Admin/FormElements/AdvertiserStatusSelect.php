<?php

namespace App\View\Components\Admin\FormElements;

use App\Models\Ad\Advertiser;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class AdvertiserStatusSelect extends Component
{
    public $options;

    public function __construct()
    {
        foreach(Advertiser::$statuses as $key => $label) {
            $this->options[] = [
                'value' => $key,
                'label' => $label
            ];
        }
    }

    public function render(): View|Closure|string
    {
        return view('components.admin.form-elements.advertiser-status-select');
    }
}
