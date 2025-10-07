<?php

namespace App\Http\Requests\Admin\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'logo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'nickname' => [
                'required',
                'string',
                Rule::unique('users')->ignore($this->route('id')),
            ],
            'email' => [
                'required',
                'string',
                'email',
                Rule::unique('users')->ignore($this->route('id')),
            ],
            'name' => 'required|string|max:255',
            'surname' => 'required|string|max:255',
            'phone' => 'required',
            'gender_id' => 'required|exists:genders,id',
            'birthday' => 'required|date',
            'bio' => 'nullable|max:500',
            'slogan' => 'nullable|max:500',
            'primary_team_id' => 'required|exists:teams,id'
        ];
    }

    public function messages(): array
    {
        return [
            // Logo
            'logo.required' => __('validation.required', ['attribute' => 'Logo']),
            'logo.image' => __('validation.image', ['attribute' => 'Logo']),
            'logo.mimes' => __('validation.mimes', ['attribute' => 'Logo', 'values' => 'jpeg, png, jpg']),
            'logo.max' => __('validation.max.file', ['attribute' => 'Logo', 'max' => '2048 KB']),

            // Nickname
            'nickname.required' => __('validation.required', ['attribute' => 'Kullanıcı Adı']),
            'nickname.string' => __('validation.string', ['attribute' => 'Kullanıcı Adı']),
            'nickname.unique' => __('validation.unique', ['attribute' => 'Kullanıcı Adı']),

            // Email
            'email.required' => __('validation.required', ['attribute' => 'E-posta']),
            'email.string' => __('validation.string', ['attribute' => 'E-posta']),
            'email.email' => __('validation.email', ['attribute' => 'E-posta']),
            'email.unique' => __('validation.unique', ['attribute' => 'E-posta']),

            // Name
            'name.required' => __('validation.required', ['attribute' => 'İsim']),
            'name.string' => __('validation.string', ['attribute' => 'İsim']),
            'name.max' => __('validation.max.string', ['attribute' => 'İsim', 'max' => 255]),

            // Surname
            'surname.required' => __('validation.required', ['attribute' => 'Soyisim']),
            'surname.string' => __('validation.string', ['attribute' => 'Soyisim']),
            'surname.max' => __('validation.max.string', ['attribute' => 'Soyisim', 'max' => 255]),

            // Phone
            'phone.required' => __('validation.required', ['attribute' => 'Telefon']),
            'phone.string' => __('validation.string', ['attribute' => 'Telefon']),

            // Gender
            'gender_id.required' => __('validation.required', ['attribute' => 'Cinsiyet']),
            'gender_id.exists' => __('validation.exists', ['attribute' => 'Cinsiyet']),

            // Birthday
            'birthdate.required' => __('validation.required', ['attribute' => 'Doğum Tarihi']),
            'birthdate.date' => __('validation.date', ['attribute' => 'Doğum Tarihi']),

            // Primary Team
            'primary_team_id.required' => __('validation.required', ['attribute' => 'Takım']),
            'primary_team_id.exists' => __('validation.exists', ['attribute' => 'Takım']),
        ];
    }
}
