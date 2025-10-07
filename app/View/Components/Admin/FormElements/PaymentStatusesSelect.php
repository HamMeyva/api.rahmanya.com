<?php

namespace App\View\Components\Admin\FormElements;

use App\Models\Morph\Payment;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class PaymentStatusesSelect extends Component
{
    public $options;

    public function __construct()
    {
        foreach(Payment::$statuses as $key => $label) {
            $this->options[] = [
                'value' => $key,
                'label' => $label
            ];
        }
    }

    public function render(): View|Closure|string
    {
        return view('components.admin.form-elements.payment-statuses-select');
    }
}
