<?php

namespace App\Http\Requests\Admin\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:admins,email',
            'password' => 'required|string|min:8',
            'role_ids' => 'required|array',
            'role_ids.*' => 'exists:roles,id',
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => __('validation.required', ['attribute' => 'Ad']),
            'last_name.required' => __('validation.required', ['attribute' => 'Soyad']),
            'email.required' => __('validation.required', ['attribute' => 'E-Posta']),
            'role_ids.required' => __('validation.required', ['attribute' => 'Rol']),
            'role_ids.*.exists' => __('validation.exists', ['attribute' => 'Rol']),
        ];
    }
}
