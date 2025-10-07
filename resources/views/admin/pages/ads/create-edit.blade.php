@extends('admin.template')
@use('App\Helpers\CommonHelper')
@use('App\Models\Ad\Ad')
@section('title', isset($ad) ? 'Reklam Düzenle' : 'Reklam Ekle')
@section('breadcrumb')
    <x-admin.breadcrumb :data="[isset($ad) ? 'Reklam Düzenle' : 'Reklam Ekle', 'Reklamlar' => route('admin.ads.index')]" :backUrl="route('admin.ads.index')" />
@endsection
@section('master')
    <form id="primaryForm" class="form d-flex flex-column flex-lg-row"
        action="{{ isset($ad) ? route('admin.ads.update', ['id' => $ad->id]) : route('admin.ads.store') }}">
        @csrf
        <!-- begin::Aside-->
        <div class="d-flex flex-column gap-7 gap-lg-10 w-100 w-lg-300px mb-7 me-lg-10">
            <!--begin::Thumbnail settings-->
            <div class="card card-flush py-4">
                <!--begin::Card header-->
                <div class="card-header">
                    <!--begin::Card title-->
                    <div class="card-title">
                        <h2>Görsel</h2>
                    </div>
                    <!--end::Card title-->
                </div>
                <!--end::Card header-->

                <!--begin::Card body-->
                <div class="card-body text-center pt-0">
                    <!--begin::Image or Video input-->
                    <div class="mb-3">
                        <input type="file" accept=".png, .jpg, .jpeg, .mp4" name="media_path" class="form-control">
                        <div class="form-text">Sadece .png, .jpg, .jpeg, .mp4 uzantılı dosyalar kabul edilir.</div>
                    </div>
                    <!--end::Image or Video input-->
                </div>
                <!--end::Card body-->
            </div>
            <!--end::Thumbnail settings-->
            <!--begin::Thumbnail settings-->
            @if (isset($ad))
                <div class="card card-flush py-4 d-none">
                    <!--begin::Card header-->
                    <div class="card-header">
                        <!--begin::Card title-->
                        <div class="card-title">
                            <h2>{{ $ad->media_type_id === Ad::MEDIA_TYPE_VIDEO ? 'Video' : 'Görsel' }}</h2>
                        </div>
                        <!--end::Card title-->
                    </div>
                    <!--end::Card header-->

                    <!--begin::Card body-->
                    <div class="card-body text-center pt-0">
                        <div class="">
                            @if ($ad->media_type_id === Ad::MEDIA_TYPE_VIDEO)
                                <video class="img img-fluid"
                                    src="{{ isset($ad?->media_url) ? $ad?->media_url : 'none' }}"></video>
                            @else
                                <img class="img img-fluid" src="{{ isset($ad?->media_url) ? $ad?->media_url : 'none' }}">
                            @endif
                        </div>
                    </div>
                    <!--end::Card body-->
                </div>
            @endif
            <!--end::Thumbnail settings-->
        </div>
        <!-- end::Aside-->

        <!-- begin::Main Content-->
        <div class="col-lg-8 d-flex flex-column flex-row-fluid gap-7 gap-lg-10">
            <div class="card card-flush py-4">
                <!--begin::Card header-->
                <div class="card-header">
                    <div class="card-title">
                        <h2>Reklam Bilgileri</h2>
                    </div>
                </div>
                <!--end::Card header-->

                <!--begin::Card body-->
                <div class="card-body pt-0 row g-3">
                    <div class="col-12">
                        <h3>Genel</h3>
                        <div class="separator separator-dashed mb-5"></div>
                        <div class="row g-5">
                            <div class="col-xl-12">
                                <label class="form-label required">Reklam Yeri</label>
                                <x-admin.form-elements.placement-select name="placement_ids[]" :selectedOption="isset($ad->placements) ? $ad->placements->pluck('id')->toArray() : null"
                                    :hideSearch="true" customAttr="multiple=true" />
                            </div>
                            <div class="col-xl-6">
                                <label class="form-label required">Reklam Veren</label>
                                <x-admin.form-elements.advertiser-select name="advertiser_id" :selectedOption="isset($ad->advertiser)
                                    ? ['label' => $ad->advertiser->name, 'value' => $ad->advertiser_id]
                                    : null" />
                            </div>
                            <div class="col-xl-6">
                                <label class="form-label required">Reklamın Yönlendirdiği URL</label>
                                <input type="url" name="redirect_url" class="form-control mb-2"
                                    value="{{ $ad?->redirect_url ?? null }}">
                            </div>
                            <div class="col-xl-12">
                                <label class="form-label required">Reklam Başlığı</label>
                                <input type="text" name="title" class="form-control mb-2"
                                    value="{{ $ad?->title ?? null }}">
                            </div>
                            <div class="col-xl-12">
                                <label class="form-label">Reklam Açıklaması</label>
                                <textarea name="description" class="form-control mb-2">{{ $ad?->description ?? null }}</textarea>
                            </div>
                            <div class="col-xl-4">
                                <label class="form-label required">Gösterim Başlangıç Tarihi</label>
                                <x-admin.form-elements.date-input name="start_date" :value="isset($ad) ? $ad?->start_date : null"/>
                            </div>
                            <div class="col-xl-4">
                                <label class="form-label required">Gösterim Başlangıç Saati</label>
                                <input type="time" name="show_start_time" class="form-control mb-2"
                                    value="{{ isset($ad) ? $ad?->show_start_time?->format('H:i') : null }}">
                            </div>
                            <div class="col-xl-4">
                                <label class="form-label required">Gösterim Bitiş Saati</label>
                                <input type="time" name="show_end_time" class="form-control mb-2"
                                    value="{{ isset($ad) ? $ad?->show_end_time?->format('H:i') : null }}">
                            </div>
                        </div>
                    </div>
                    <div class="col-12 mt-8">
                        <h3>Bütçe</h3>
                        <div class="separator separator-dashed mb-5"></div>
                        <div class="row g-5">
                            <div class="col-xl-4">
                                <label class="form-label required">Reklamın Toplam Bütçesi</label>
                                <input name="total_budget" class="form-control mb-2 price-input"
                                    value="{{ isset($ad) ? $ad?->get_total_budget : null }}" min="1" step="1">
                            </div>
                            <div class="col-xl-4">
                                <label class="form-label required">Toplam Gösterim Saati</label>
                                <input type="number" name="total_hours" class="form-control mb-2" placeholder="0"
                                    value="{{ isset($ad) ? $ad?->total_hours : null }}" min="1" step="1">
                            </div>
                            <div class="col-xl-4">
                                <label class="form-label d-flex justify-content-between">
                                    <span>Saat Başına Teklif</span>
                                    <span class="badge badge-success cursor-pointer calculate-bid-amount">Hesapla</span>
                                </label>
                                <input disabled name="bid_amount" class="form-control mb-2 price-input text-gray-600"
                                    value="{{ isset($ad) ? $ad?->get_bid_amount : null }}" min="1" step="1">
                            </div>
                        </div>
                    </div>
                    <div class="col-12 mt-8">
                        <h3>Hedefleme Kriterleri</h3>
                        <div class="separator separator-dashed mb-5"></div>
                        <div class="row g-5">
                            <div class="col-xl-6">
                                <label class="form-label required">Ülke</label>
                                <x-admin.form-elements.country-select name="target_country_id" :selectedOption="isset($ad) ? $ad?->target_country_id : null" />
                            </div>
                            <div class="col-xl-6">
                                <label class="form-label">Şehir</label>
                                @php
                                    /*$citySelectedOption = null;
                                    if (isset($ad) && $ad->target_cities) {
                                        $citySelectedOption = $ad->target_cities
                                            ->map(function ($city) {
                                                return [
                                                    'label' => $city->name,
                                                    'value' => $city->id,
                                                ];
                                            })
                                            ->toArray();*/
                                    //city-select componentine bir geliştirme atmak lazım mulitple ve select2 oldugudna selectedları ekleme özelliği yok suan için.
                                @endphp
                                <x-admin.form-elements.city-select name="target_city_ids[]" placeholder="Tümü"
                                    :selectedOption="null" :hideSearch="true" customAttr="multiple=true" />
                            </div>
                            <div class="col-xl-6">
                                <label class="form-label">Yaş</label>
                                <x-admin.form-elements.age-range-select name="target_age_range_ids[]" placeholder="Tümü"
                                    :selectedOption="isset($ad) ? $ad?->target_age_range_ids : null" :hideSearch="true" customAttr="multiple=true" />
                            </div>
                            <div class="col-xl-6">
                                <label class="form-label">Cinsiyet</label>
                                <x-admin.form-elements.gender-select name="target_gender_ids[]" placeholder="Tümü"
                                    :selectedOption="isset($ad) ? $ad?->target_gender_ids : null" :hideSearch="true" customAttr="multiple=true" />
                            </div>
                            <div class="col-xl-6">
                                <label class="form-label">Takım</label>
                                <x-admin.form-elements.team-select name="target_team_ids[]" placeholder="Tümü"
                                    :selectedOption="isset($ad) ? $ad?->target_team_ids : null" :hideSearch="true" customAttr="multiple=true" />
                            </div>
                            <div class="col-xl-6">
                                <label class="form-label required">Dil</label>
                                <x-admin.form-elements.language-select name="target_language_id" :selectedOption="$ad?->target_language_id ?? null"
                                    :hideSearch="true" />
                            </div>
                            <div class="col-xl-6">
                                <label class="form-label">İşletim Sistemi</label>
                                <x-admin.form-elements.os-select name="target_os_ids[]" placeholder="Tümü"
                                    :selectedOption="isset($ad) && $ad?->target_oses ? $ad?->target_oses->pluck('id')->toArray() : null" :hideSearch="true" customAttr="multiple=true" />
                            </div>
                        </div>
                    </div>
                    <div class="col-12 mt-8 text-end">
                        <x-admin.form-elements.submit-btn>{{ isset($ad) ? 'Düzenle' : 'Ekle' }}</x-admin.form-elements.submit-btn>
                    </div>
                </div>
                <!--end::Card body-->
            </div>
        </div>
        <!-- end::Main Content-->
    </form>
@endsection
@section('scripts')
    <script>
        $(document).ready(function() {
            $(document).on("submit", "#primaryForm", function(e) {
                e.preventDefault();

                let formData = new FormData(this),
                    submitBtn = $(this).find('button[type="submit"]'),
                    url = $(this).attr('action');

                if ($(this).find('.image-input').hasClass('image-input-changed')) {
                    formData.append('image_changed', 1);
                }

                $.ajax({
                    type: 'POST',
                    url: url,
                    data: formData,
                    dataType: 'json',
                    contentType: false,
                    processData: false,
                    cache: false,
                    beforeSend: function() {
                        propSubmitButton(submitBtn, 1);
                    },
                    success: function(res) {
                        swal.success({
                            message: res.message
                        }).then(() => window.location.href = res?.redirect_url)
                    },
                    error: function(xhr) {
                        swal.error({
                            message: xhr.responseJSON?.message ?? null
                        })
                    },
                    complete: function() {
                        propSubmitButton(submitBtn, 0);
                    }
                })
            })
        })
    </script>
    <script>
        $(document).ready(function() {
            const totalBudgetInput = $('[name="total_budget"]')
            const totalHoursInput = $('[name="total_hours"]')
            const bidAmountInput = $('[name="bid_amount"]')

            $(document).on("click", ".calculate-bid-amount", function(e) {
                const totalBudget = totalBudgetInput.val()
                const totalHours = totalHoursInput.val()

                if (totalBudget && totalHours) {
                    const totalCost = parseFloat((formatDecimalInput(totalBudget) / totalHours).toFixed(2))
                    bidAmountInput.val(priceFormat.to(totalCost))
                } else {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Uyarı!',
                        html: 'Bütçe hesaplanması için <b>Toplam Bütçe</b> ve <b>Toplam Gösterim Saati</b> giriniz.',
                        showConfirmButton: false,
                        showCancelButton: true,
                        allowOutsideClick: false,
                        buttonsStyling: false,
                        cancelButtonText: 'Kapat',
                        customClass: {
                            cancelButton: 'btn btn-secondary btn-sm'
                        }
                    })
                    return
                }
            })
        })
    </script>
@endsection
