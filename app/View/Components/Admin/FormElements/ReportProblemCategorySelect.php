<?php

namespace App\View\Components\Admin\FormElements;

use Closure;
use Illuminate\View\Component;
use Illuminate\Contracts\View\View;
use App\Models\Fake\ReportProblemCategory;

class ReportProblemCategorySelect extends Component
{
    public $options;
    public function __construct()
    {
        $this->options = ReportProblemCategory::query()
            ->get()
            ->map(function ($item) {
                return [
                    'value' => $item->id,
                    'label' => $item->name,
                ];
            });
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.admin.form-elements.report-problem-category-select');
    }
}
