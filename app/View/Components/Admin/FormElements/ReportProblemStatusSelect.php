<?php

namespace App\View\Components\Admin\FormElements;

use App\Models\Morph\ReportProblem;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class ReportProblemStatusSelect extends Component
{
    public $options;
    public function __construct()
    {
        foreach(ReportProblem::$statuses as $key => $label) {
            $this->options[] = [
                'value' => $key,
                'label' => $label
            ];
        }
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.admin.form-elements.report-problem-status-select');
    }
}
