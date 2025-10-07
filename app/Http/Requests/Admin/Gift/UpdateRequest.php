<?php

namespace App\Http\Requests\Admin\Gift;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    protected function prepareForValidation()
    {
        $assets = $this->input('asset_repeater_area', []);

        // İlk elemanı kaldır. repeater'da döngüden dolayı boş bir eleman geliyor.
        unset($assets[0]);

        // Indexleri sıfırla
        $this->merge([
            'asset_repeater_area' => $assets,
        ]);
    }

    public function rules(): array
    {
        return [
            'is_active' => 'nullable|boolean',
            'name' => 'required|string',
            'price' => 'required|integer|min:1',
            'discounted_price' => 'nullable|integer|min:1',
            'queue' => 'nullable|numeric',
            'asset_repeater_area' => 'required|array',
            'asset_repeater_area.*.team_id' => 'nullable|exists:teams,id',
            'asset_repeater_area.*.image_path' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'asset_repeater_area.*.video_path' => 'nullable|file|mimes:mp4,gif',
        ];
    }

    public function messages(): array
    {
        return [
            // Name
            'name.required' => __('validation.required', ['attribute' => 'Hediye Adı']),

            // Price
            'price.required' => __('validation.required', ['attribute' => 'Hediye Fiyatı']),
            'price.numeric' => __('validation.numeric', ['attribute' => 'Hediye Fiyatı']),
            'price.integer' => __('validation.integer', ['attribute' => 'Hediye Fiyatı']),
            'price.min' => __('validation.min.numeric', ['attribute' => 'Hediye Fiyatı', 'min' => '1']),

            // Discounted Price
            'discounted_price.numeric' => __('validation.numeric', ['attribute' => 'İndirimli Fiyat']),
            'discounted_price.integer' => __('validation.integer', ['attribute' => 'İndirimli Fiyat']),
            'discounted_price.min' => __('validation.min.numeric', ['attribute' => 'İndirimli Fiyat', 'min' => '1']),

            // Queue
            'queue.numeric' => __('validation.numeric', ['attribute' => 'Sıra']),

            // Asset Repeater Area
            'asset_repeater_area.required' => __('validation.required', ['attribute' => 'Varyasyon Alanı']),
            'asset_repeater_area.array' => __('validation.min.array', ['attribute' => 'Varyasyon Alanı', 'min' => '1']),

            // Team ID (nested)
            'asset_repeater_area.*.team_id.exists' => __('validation.exists', ['attribute' => 'Takım', 'table' => 'teams']),

            // Image Path (nested)
            'asset_repeater_area.*.image_path.image' => __('validation.image', ['attribute' => 'Varyasyon Görsel']),
            'asset_repeater_area.*.image_path.mimes' => __('validation.mimes', ['attribute' => 'Varyasyon Görsel', 'values' => 'jpeg, png, jpg']),
            'asset_repeater_area.*.image_path.max' => __('validation.max.file', ['attribute' => 'Varyasyon Görsel', 'max' => '2 MB']),

            // Video Path (nested)
            'asset_repeater_area.*.video_path.file' => __('validation.file', ['attribute' => 'Varyasyon Video']),
            'asset_repeater_area.*.video_path.mimes' => __('validation.mimes', ['attribute' => 'Varyasyon Video', 'values' => 'mp4, gif']),
            'asset_repeater_area.*.video_path.max' => __('validation.max.file', ['attribute' => 'Varyasyon Video', 'max' => '15 MB']),

        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $assetAreas = $this->input('asset_repeater_area', []);

            $teamIds = collect($assetAreas)
                ->pluck('team_id');

            $nullCount = $teamIds->filter(fn($id) => $id === null)->count();

            // En fazla bir adet null olmalı
            if ($nullCount > 1) {
                $validator->errors()->add('asset_repeater_area', 'En fazla bir adet takımsız varyasyon eklenebilir.');
            }

            // Dolu olan team_id'ler benzersiz olmalı
            $filledTeamIds = $teamIds->filter();
            if ($filledTeamIds->count() !== $filledTeamIds->unique()->count()) {
                $validator->errors()->add('asset_repeater_area', 'Aynı takım birden fazla kez seçilemez.');
            }
        });
    }
}
