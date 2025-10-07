<?php

namespace App\Http\Requests\Admin\LiveStreamCatagory;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'icon' => 'nullable|string|max:255',
            'display_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean'
        ];
    }

    public function messages(): array
    {
        return [
            // Name
            'name.required' => __('validation.required', ['attribute' => 'Ad']),

            // Icon
            'icon.required' => __('validation.required', ['attribute' => 'Icon']),
            'icon.image' => __('validation.image', ['attribute' => 'Icon']),
            'icon.mimes' => __('validation.mimes', ['attribute' => 'Icon', 'values' => 'jpeg, png, jpg']),
            'icon.max' => __('validation.max.file', ['attribute' => 'Icon', 'max' => '2048 KB']),

            // Display Order
            'display_order.numeric' => __('validation.numeric', ['attribute' => 'Görüntüleme Sırası']),

            // Active
            'is_active.boolean' => __('validation.boolean', ['attribute' => 'Durum']),
        ];
    }
}
