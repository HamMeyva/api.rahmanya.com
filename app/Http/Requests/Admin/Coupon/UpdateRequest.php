<?php

namespace App\Http\Requests\Admin\Coupon;

use App\Models\Coupon;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'code' => "required|string|max:255|unique:coupons,code,{$this->coupon->id}",
            'discount_type' => 'required|string|in:'.Coupon::DISCOUNT_TYPE_PERCENTAGE .','.Coupon::DISCOUNT_TYPE_FIXED,
            'country_id' => 'required|integer|exists:countries,id',
            'discount_amount' => 'required|numeric|min:1',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'max_usage' => 'required|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => __('validation.required', ['attribute' => 'Kupon Kodu']),
            'code.max' => __('validation.max.string', ['attribute' => 'Kupon Kodu', 'max' => 255]),
            'code.unique' => __('validation.unique', ['attribute' => 'Kupon Kodu']),

            'discount_type.required' => __('validation.required', ['attribute' => 'İndirim Tipi']),
            'discount_type.integer' => __('validation.integer', ['attribute' => 'İndirim Tipi']),
            'discount_type.in' => __('validation.in', ['attribute' => 'İndirim Tipi', 'values' => implode(',', [Coupon::DISCOUNT_TYPE_PERCENTAGE, Coupon::DISCOUNT_TYPE_FIXED])]),

            'country_id.required' => __('validation.required', ['attribute' => 'Ülke']),
            'country_id.integer' => __('validation.integer', ['attribute' => 'Ülke']),
            'country_id.exists' => __('validation.exists', ['attribute' => 'Ülke']),

            'discount_amount.required' => __('validation.required', ['attribute' => 'İndirim Tutarı']),
            'discount_amount.numeric' => __('validation.numeric', ['attribute' => 'İndirim Tutarı']),
            'discount_amount.min' => __('validation.min.numeric', ['attribute' => 'İndirim Tutarı', 'min' => 1]),

            'start_date.required' => __('validation.required', ['attribute' => 'Başlangıç Tarihi']),
            'start_date.date' => __('validation.date', ['attribute' => 'Başlangıç Tarihi']),

            'end_date.required' => __('validation.required', ['attribute' => 'Bitiş Tarihi']),
            'end_date.date' => __('validation.date', ['attribute' => 'Bitiş Tarihi']),

            'max_usage.required' => __('validation.required', ['attribute' => 'Maks Kullanım']),
            'max_usage.integer' => __('validation.integer', ['attribute' => 'Maks Kullanım']),
            'max_usage.min' => __('validation.min.numeric', ['attribute' => 'Maks Kullanım', 'min' => 1]),
        ];
    }
}
