<?php

namespace App\View\Components\Admin\FormElements;

use Closure;
use App\Models\Morph\Payment;
use Illuminate\View\Component;
use Illuminate\Contracts\View\View;

class PaymentChannelSelect extends Component
{
    public $options;

    public function __construct()
    {
        foreach (Payment::$channels as $key => $label) {
            $this->options[] = [
                'value' => $key,
                'label' => $label
            ];
        }
    }
    
    public function render(): View|Closure|string
    {
        return view('components.admin.form-elements.payment-channel-select');
    }
}
