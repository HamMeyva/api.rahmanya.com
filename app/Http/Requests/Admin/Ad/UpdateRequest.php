<?php

namespace App\Http\Requests\Admin\Ad;

use App\Models\Demographic\Os;
use App\Models\Demographic\Gender;
use App\Models\Demographic\AgeRange;
use App\Models\Demographic\Language;
use App\Models\Demographic\Placement;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'media_path' => 'nullable|file|mimes:png,jpg,jpeg,mp4|max:102400',
            'placement_ids' => 'required|array',
            'placement_ids.*' => 'in:' . implode(',', array_keys(Placement::$placements)),
            'advertiser_id' => 'required|exists:advertisers,id',
            'redirect_url' => 'required|url',
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:400',
            'start_date' => 'required|date',
            'show_start_time' => 'required|date_format:H:i',
            'show_end_time' => 'required|date_format:H:i',
            'total_budget' => 'required',
            'total_hours' => 'required|numeric',
            'target_country_id' => 'required|exists:countries,id',
            'target_city_ids' => 'nullable|exists:cities,id',
            'target_age_range_ids' => 'nullable|array',
            'target_age_range_ids.*' => 'in:' . implode(',', array_keys(AgeRange::$ageRanges)),
            'target_gender_ids' => 'nullable|array',
            'target_gender_ids.*' => 'in:' . implode(',', array_keys(Gender::$genders)),
            'target_team_ids' => 'nullable|array',
            'target_team_ids.*' => 'exists:teams,id',
            'target_language_id' => 'required|in:' . implode(',', array_keys(Language::$languages)),
            'target_os_ids' => 'nullable|array',
            'target_os_ids.*' => 'in:' . implode(',', array_keys(Os::$oses)),
        ];
    }

    public function messages(): array
    {
        return [
            'media_path.mimes' => __('validation.mimes', ['attribute' => 'Görsel', 'values' => 'png,jpg,jpeg,mp4']),
            'media_path.max' => __('validation.max.file', ['attribute' => 'Görsel', 'max' => 102400]),
            'placement_id.required' => __('validation.required', ['attribute' => 'Reklam Yeri']),
            'placement_id.in' => __('validation.in', ['attribute' => 'Reklam Yeri', 'values' => implode(', ', array_keys(Placement::$placements))]),
            'advertiser_id.required' => __('validation.required', ['attribute' => 'Reklam Veren']),
            'advertiser_id.exists' => __('validation.exists', ['attribute' => 'Reklam Veren', 'values' => 'advertisers,id']),
            'redirect_url.required' => __('validation.required', ['attribute' => 'Yönlendirme URL']),
            'redirect_url.url' => __('validation.url', ['attribute' => 'Yönlendirme URL']),
            'title.required' => __('validation.required', ['attribute' => 'Başlık']),
            'title.max' => __('validation.max.string', ['attribute' => 'Başlık', 'max' => 255]),
            'description.required' => __('validation.required', ['attribute' => 'Açıklama']),
            'description.max' => __('validation.max.string', ['attribute' => 'Açıklama', 'max' => 400]),
            'start_date.required' => __('validation.required', ['attribute' => 'Başlangıç Tarihi']),
            'show_start_time.required' => __('validation.required', ['attribute' => 'Gösterim Başlangıç Saati']),
            'show_start_time.date_format' => __('validation.date_format', ['attribute' => 'Gösterim Başlangıç Saati', 'format' => 'H:i']),
            'show_end_time.required' => __('validation.required', ['attribute' => 'Gösterim Bitiş Saati']),
            'show_end_time.date_format' => __('validation.date_format', ['attribute' => 'Gösterim Bitiş Saati', 'format' => 'H:i']),
            'total_budget.required' => __('validation.required', ['attribute' => 'Toplam Bütçe']),
            'total_hours.required' => __('validation.required', ['attribute' => 'Toplam Saat']),
            'target_country_id.required' => __('validation.required', ['attribute' => 'Ülke']),
            'target_country_id.exists' => __('validation.exists', ['attribute' => 'Ülke', 'values' => 'countries,id']),
            'target_city_ids.exists' => __('validation.exists', ['attribute' => 'Şehir', 'values' => 'cities,id']),
            'target_language_id.required' => __('validation.required', ['attribute' => 'Dil']),
        ];
    }
}
