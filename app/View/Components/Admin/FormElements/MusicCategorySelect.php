<?php

namespace App\View\Components\Admin\FormElements;

use Closure;
use Illuminate\View\Component;
use App\Models\Music\MusicCategory;
use Illuminate\Contracts\View\View;

class MusicCategorySelect extends Component
{
    public $options;

    public function __construct()
    {
        $this->options = MusicCategory::query()
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
        return view('components.admin.form-elements.music-category-select');
    }
}
