<?php

namespace App\Http\Requests\Admin\Team;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'logo' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'name' => 'required',
            'color1' => 'required',
            'color2' => 'required'
        ];
    }

    public function messages(): array
    {
        return [
            // Name
            'name.required' => __('validation.required', ['attribute' => 'Takım Adı']),

            // Logo
            'logo.required' => __('validation.required', ['attribute' => 'Logo']),
            'logo.image' => __('validation.image', ['attribute' => 'Logo']),
            'logo.mimes' => __('validation.mimes', ['attribute' => 'Logo', 'values' => 'jpeg, png, jpg']),
            'logo.max' => __('validation.max.file', ['attribute' => 'Logo', 'max' => '2048 KB']),
        ];
    }
}
