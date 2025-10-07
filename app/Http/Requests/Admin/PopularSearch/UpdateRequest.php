<?php

namespace App\Http\Requests\Admin\PopularSearch;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'title' => 'required',
            'queue' => 'nullable|numeric',
        ];
    }

    public function messages(): array
    {
        return [
            // Title
            'title.required' => __('validation.required', ['attribute' => 'Başlık']),

            // Logo
            'image.image' => __('validation.image', ['attribute' => 'Görsel']),
            'image.mimes' => __('validation.mimes', ['attribute' => 'Görsel', 'values' => 'jpeg, png, jpg']),
            'image.max' => __('validation.max.file', ['attribute' => 'Görsel', 'max' => '2048 KB']),


            // Queue
            'queue.numeric' => __('validation.numeric', ['attribute' => 'Sıra']),
        ];
    }
}
