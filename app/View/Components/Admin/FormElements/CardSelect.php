<?php

namespace App\View\Components\Admin\FormElements;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use App\Models\Punishment;

class CardSelect extends Component
{
    public $options;
    public function __construct()
    {
        $this->options = [
            [
                "label" => "Sarı Kart",
                "value" => Punishment::YELLOW_CARD
            ],
            [
                "label" => "Kırmızı Kart",
                "value" => Punishment::RED_CARD
            ]
        ];
    }

    public function render(): View|Closure|string
    {
        return view('components.admin.form-elements.card-select');
    }
}
