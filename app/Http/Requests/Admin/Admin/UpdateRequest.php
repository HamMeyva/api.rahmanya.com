<?php

namespace App\Http\Requests\Admin\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => "required|email|max:255|unique:admins,email,{$this->id}",
            'password' => 'nullable|string|min:8',
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
            'email.unique' => __('validation.unique', ['attribute' => 'E-Posta']),
            'password.required' => __('validation.required', ['attribute' => 'Parola']),
            'password.min' => __('validation.min.string', ['attribute' => 'Parola', 'min' => 8]),
            'role_ids.required' => __('validation.required', ['attribute' => 'Rol']),
            'role_ids.*.exists' => __('validation.exists', ['attribute' => 'Rol']),
        ];
    }
}
