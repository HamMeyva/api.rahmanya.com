<?php

namespace App\View\Components\Admin\FormElements;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Spatie\Permission\Models\Role;

class RoleSelect extends Component
{
    public $options;
    public function __construct()
    {
        $this->options = Role::query()
            ->orderBy('id', 'asc')
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
        return view('components.admin.form-elements.role-select');
    }
}
