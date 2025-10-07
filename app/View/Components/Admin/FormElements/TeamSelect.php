<?php

namespace App\View\Components\Admin\FormElements;

use App\Models\Relations\Team;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class TeamSelect extends Component
{
    public $options;

    public function __construct()
    {
        $this->options = Team::query()
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
        return view('components.admin.form-elements.team-select');
    }
}
