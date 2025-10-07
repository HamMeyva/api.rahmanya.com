<?php

namespace App\View\Components\Admin\FormElements;

use App\Models\PunishmentCategory;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class PunishmentCategorySelect extends Component
{
    public $options;

    public function __construct()
    {
        $this->options = PunishmentCategory::with('children')->whereNull('parent_id')->get();
    }

    public function render(): View|Closure|string
    {
        return view('components.admin.form-elements.punishment-category-select');
    }
}
