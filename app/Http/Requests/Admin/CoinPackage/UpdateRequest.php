<?php

namespace App\Http\Requests\Admin\CoinPackage;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'coin_amount' => 'required|integer|min:1',
            'price' => 'required',
            'discounted_price' => 'nullable',
            'currency_id' => 'required|integer|exists:currencies,id',
            'country_id' => 'required|integer|exists:countries,id',
        ];
    }

    public function messages(): array
    {
        return [
            'coin_amount.required' => __('validation.required', ['attribute' => 'Coin Miktarı']),
            'price.required' => __('validation.required', ['attribute' => 'Fiyat']),
            'currency_id.required' => __('validation.required', ['attribute' => 'Para Birimi']),
            'country_id.required' => __('validation.required', ['attribute' => 'Ülke']),
        ];
    }
}
