<?php

namespace App\Http\Requests\Admin\Punishment;

use App\Models\Punishment;
use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'is_direct_punishment' => 'nullable|boolean',
            'description' => 'required',
            'card_type_id' => 'required|in:' . Punishment::YELLOW_CARD . ',' . Punishment::RED_CARD,
            'punishment_category_id' => 'required|exists:punishment_categories,id'
        ];
    }

    public function messages(): array
    {
        return [
            // Is Direct Punishment
            'is_direct_punishment.boolean' => __('validation.boolean', ['attribute' => 'Doğrudan Ceza']),

            // Description
            'description.required' => __('validation.required', ['attribute' => 'Ceza Açıklaması']),

            // Card Type
            'card_type_id.required' => __('validation.required', ['attribute' => 'Kart']),
            'card_type_id.in' => __('validation.in', ['attribute' => 'Kart']),

            // Punishment Category
            'punishment_category_id.required' => __('validation.required', ['attribute' => 'Kategori']),
            'punishment_category_id.exists' => __('validation.exists', ['attribute' => 'Kategori']),
        ];
    }
}
