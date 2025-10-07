<?php

namespace App\Http\Requests\Admin\CoinPackage;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'package_repeater_condition_area' => 'required|array',
            'package_repeater_condition_area.*.coin_amount' => 'required|integer|min:1',
            'package_repeater_condition_area.*.price' => 'required',
            'package_repeater_condition_area.*.discounted_price' => 'nullable',
            'package_repeater_condition_area.*.currency_id' => 'required|integer|exists:currencies,id',
            'package_repeater_condition_area.*.country_id' => 'required|integer|exists:countries,id',
        ];
    }

    public function messages(): array
    {
        return [
            'package_repeater_condition_area.required' => 'En az bir satır eklemelisiniz.',
            'package_repeater_condition_area.*.coin_amount.required' => __('validation.required', ['attribute' => 'Coin Miktarı']),
            'package_repeater_condition_area.*.price.required' => __('validation.required', ['attribute' => 'Fiyat']),
            'package_repeater_condition_area.*.currency_id.required' => __('validation.required', ['attribute' => 'Para Birimi']),
            'package_repeater_condition_area.*.country_id.required' => __('validation.required', ['attribute' => 'Ülke']),
        ];
    }
}
