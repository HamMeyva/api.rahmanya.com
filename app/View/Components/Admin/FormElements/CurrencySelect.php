<?php

namespace App\View\Components\Admin\FormElements;

use App\Models\Common\Currency;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class CurrencySelect extends Component
{
    public $options;

    public function __construct()
    {
        $this->options = Currency::query()
            ->get()
            ->map(function ($item) {
                return [
                    'value' => $item->id,
                    'label' => "{$item->name} - {$item->symbol}",
                ];
            });
    }
    public function render(): View|Closure|string
    {
        return view('components.admin.form-elements.currency-select');
    }
}
