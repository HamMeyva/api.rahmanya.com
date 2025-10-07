<?php

namespace App\View\Components\Admin\FormElements;

use Closure;
use App\Models\Ad\Ad;
use Illuminate\View\Component;
use Illuminate\Contracts\View\View;

class AdPaymentStatusSelect extends Component
{
    public $options;

    public function __construct()
    {
        foreach (Ad::$paymentStatuses as $key => $label) {
            $this->options[] = [
                'value' => $key,
                'label' => $label
            ];
        }
    }

    public function render(): View|Closure|string
    {
        return view('components.admin.form-elements.ad-payment-status-select');
    }
}
