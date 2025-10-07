<?php

namespace App\View\Components\Admin\FormElements;

use App\Models\Coin\CoinWithdrawalRequest;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class WithdrawalRequestStatusSelect extends Component
{
    public $options;

    public function __construct()
    {
        foreach (CoinWithdrawalRequest::$statuses as $key => $label) {
            $this->options[] = [
                'value' => $key,
                'label' => $label
            ];
        }
    }
    public function render(): View|Closure|string
    {
        return view('components.admin.form-elements.withdrawal-request-status-select');
    }
}
