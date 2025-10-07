<?php

namespace App\View\Components\Admin\FormElements;

use App\Models\Music\Artist;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class ArtistSelect extends Component
{
    public $options;

    public function __construct()
    {
        $this->options = Artist::query()
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
        return view('components.admin.form-elements.artist-select');
    }
}
