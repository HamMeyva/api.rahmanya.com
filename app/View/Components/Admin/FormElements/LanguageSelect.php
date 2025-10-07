<?php

namespace App\View\Components\Admin\FormElements;

use Closure;
use App\Models\Demographic\Language;
use Illuminate\View\Component;
use Illuminate\Contracts\View\View;

class LanguageSelect extends Component
{
    public $options;

    public function __construct()
    {
        $this->options = Language::query()
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
        return view('components.admin.form-elements.language-select');
    }
}
