@extends('admin.template')
@use('App\Helpers\CommonHelper')
@use('App\Models\Ad\Ad')
@section('title', (new CommonHelper())->limitText($ad->title))
@section('breadcrumb')
    <x-admin.breadcrumb :data="[(new CommonHelper())->limitText($ad->title), 'Reklamlar' => route('admin.ads.index')]" :backUrl="route('admin.ads.index')" />
@endsection
@section('styles')
    <style>
        div[data-content-area].loading {
            filter: blur(1px);
            opacity: 0.3;
            pointer-events: none;
        }

        [data-loading] {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 2;
        }
    </style>
@endsection
@section('master')
    <div class="card mb-5 mb-xxl-8">
        <div class="card-body pt-9 pb-0">
            <!--begin::Details-->
            <div class="d-flex flex-wrap flex-sm-nowrap">
                <!--begin: Pic-->
                <div class="me-7 mb-4">
                    <div class="symbol symbol-100px symbol-lg-175px symbol-fixed position-relative">
                        <img src="{{ $ad->thumbnail_url }}" alt="Kapak Görseli">
                    </div>
                </div>
                <!--end::Pic-->

                <!--begin::Info-->
                <div class="flex-grow-1">
                    <!--begin::Title-->
                    <div class="d-flex justify-content-between align-items-start flex-wrap mb-2">
                        <!--begin::User-->
                        <div class="d-flex flex-column">
                            <!--begin::Name-->
                            <div class="d-flex align-items-center mb-2">
                                <div class="text-gray-900 text-hover-primary fs-2 fw-bold me-1">
                                    {{ (new CommonHelper())->limitText($ad->title) }}</div>
                            </div>
                            <!--end::Name-->

                            <!--begin::Info-->
                            <div class="">
                                <div class="d-flex flex-wrap fw-semibold fs-6 mb-4 pe-2">
                                    <a href="javascript:void(0);" class="d-flex align-items-center text-gray-500 me-5 mb-2">
                                        <i class="ki-duotone ki-profile-circle fs-4 me-1">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                            <span class="path3"></span>
                                        </i>{{ $ad->advertiser?->name }}</a>
                                    <a href="javascript:void(0);" class="d-flex align-items-center text-gray-500 me-5 mb-2">
                                        <i class="ki-duotone ki-sms fs-4">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>{{ $ad->advertiser?->email }}</a>
                                    <a href="{{ $ad->redirect_url }}" target="_blank"
                                        class="d-flex align-items-center text-gray-500 text-hover-primary mb-2">
                                        <i class="ki-duotone ki-geolocation fs-4 me-1">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>Reklam Bağlantısı</a>
                                </div>
                                <div class="d-flex gap-4 flex-wrap mb-4">
                                    <div class="d-flex flex-center">
                                        <label class="fw-semibold text-gray-800 me-1">Reklam Durumu:</label>
                                        <span class="badge badge-{{ $ad->get_status_color }}">{{ $ad->get_status }}</span>
                                        <span class="ms-2 editAdStatusButton" data-bs-toggle="tooltip"
                                            data-bs-placement="top" title="Reklam Durumu Güncelle">
                                            <i class="fa fa-edit text-primary cursor-pointer"></i>
                                        </span>
                                    </div>
                                    <div class="d-flex flex-center">
                                        <label class="fw-semibold text-gray-800 me-1">Ödeme Durumu:</label>
                                        <span
                                            class="badge badge-{{ $ad->get_payment_status_color }}">{{ $ad->get_payment_status }}</span>
                                        <span class="ms-2 editAdPaymentStatusButton" data-bs-toggle="tooltip"
                                            data-bs-placement="top" title="Ödeme Durumu Güncelle">
                                            <i class="fa fa-edit text-primary cursor-pointer"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <!--end::Info-->
                        </div>
                        <!--end::User-->

                        <!--begin::Actions-->
                        <div class="d-flex my-4">
                            <a href="{{ route('admin.ads.edit', ['id' => $ad->id]) }}"
                                class="btn btn-primary btn-sm d-flex flex-center">
                                <i class="fa fa-edit"></i> Düzenle
                            </a>
                        </div>
                        <!--end::Actions-->
                    </div>
                    <!--end::Title-->

                    <!--begin::Stats-->
                    <div class="d-flex flex-wrap">
                        <!--begin::Stat-->
                        <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                            <!--begin::Number-->
                            <div class="d-flex align-items-center">
                                <div class="fs-4 fw-bold counted">{{ $ad->draw_total_budget }}</div>
                            </div>
                            <!--end::Number-->
                            <!--begin::Label-->
                            <div class="fw-semibold fs-6 text-gray-500">Bütçe</div>
                            <!--end::Label-->
                        </div>
                        <!--end::Stat-->
                        <!--begin::Stat-->
                        <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                            <!--begin::Number-->
                            <div class="d-flex align-items-center">
                                <div class="fs-4 fw-bold counted">{{ $ad->draw_total_hours }}</div>
                            </div>
                            <!--end::Number-->
                            <!--begin::Label-->
                            <div class="fw-semibold fs-6 text-gray-500">Gösterim Saati</div>
                            <!--end::Label-->
                        </div>
                        <!--end::Stat-->
                        <!--begin::Stat-->
                        <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                            <!--begin::Number-->
                            <div class="d-flex align-items-center">
                                <div class="fs-4 fw-bold counted">{{ $ad->draw_bid_amount }}</div>
                            </div>
                            <!--end::Number-->
                            <!--begin::Label-->
                            <div class="fw-semibold fs-6 text-gray-500">Saat Başı Tutar</div>
                            <!--end::Label-->
                        </div>
                        <!--end::Stat-->
                    </div>
                    <div class="d-flex flex-wrap flex-stack d-none">
                        <!--begin::Wrapper-->
                        <div class="d-flex flex-column flex-grow-1 pe-8">
                            <div class="fs-4 fw-bold mb-3">Kalan Süre</div>
                            <!--begin::Stats-->
                            <div class="d-flex flex-wrap gap-5">
                                <!--begin::Stat-->
                                <div
                                    class="border border-gray-300 border-dashed rounded px-6 py-2 d-flex flex-column flex-center">
                                    <!--begin::Number-->
                                    <div class="fs-2 fw-bold">176</div>
                                    <!--end::Number-->

                                    <!--begin::Label-->
                                    <div class="fw-semibold fs-6 text-gray-500">Gün</div>
                                    <!--end::Label-->
                                </div>
                                <!--end::Stat-->
                                <!--begin::Stat-->
                                <div
                                    class="border border-gray-300 border-dashed rounded px-6 py-2 d-flex flex-column flex-center">
                                    <!--begin::Number-->
                                    <div class="fs-2 fw-bold">21</div>
                                    <!--end::Number-->

                                    <!--begin::Label-->
                                    <div class="fw-semibold fs-6 text-gray-500">Saat</div>
                                    <!--end::Label-->
                                </div>
                                <!--end::Stat-->
                                <!--begin::Stat-->
                                <div
                                    class="border border-gray-300 border-dashed rounded px-6 py-2 d-flex flex-column flex-center">
                                    <!--begin::Number-->
                                    <div class="fs-2 fw-bold">39</div>
                                    <!--end::Number-->

                                    <!--begin::Label-->
                                    <div class="fw-semibold fs-6 text-gray-500">Dakika</div>
                                    <!--end::Label-->
                                </div>
                                <!--end::Stat-->

                            </div>
                            <!--end::Stats-->
                        </div>
                        <!--end::Wrapper-->

                        <!--begin::Progress-->
                        <div class="d-flex align-items-center w-200px w-sm-300px flex-column mt-3">
                            <div class="d-flex justify-content-between w-100 mt-auto mb-2">
                                <span class="fw-semibold fs-6 text-gray-500">Davet Bilgileri</span>
                                <span class="fw-bold fs-6">50%</span>
                            </div>

                            <div class="h-5px mx-3 w-100 bg-light mb-3">
                                <div class="bg-success rounded h-5px" role="progressbar" style="width: 50%;"
                                    aria-valuenow="50" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                        <!--end::Progress-->
                    </div>
                    <!--end::Stats-->
                </div>
                <!--end::Info-->
            </div>
            <!--end::Details-->

            <!--begin:::Navs-->
            <ul class="nav nav-stretch nav-line-tabs nav-line-tabs-2x border-transparent fs-5 fw-bold mt-10">
                <!--begin:::Tab item-->
                <li class="nav-item">
                    <a class="nav-link text-active-primary pb-4 active" data-bs-toggle="tab" href="#kt_overview_tab">Genel
                        Bakış</a>
                </li>
                <!--end:::Tab item-->
                <!--begin:::Tab item-->
                <li class="nav-item">
                    <a class="nav-link text-active-primary pb-4" data-bs-toggle="tab" href="#kt_stats_tab">Değerlendirme</a>
                </li>
                <!--end:::Tab item-->
            </ul>
            <!--end:::Navs-->
        </div>
    </div>

    <!--begin:::Tab content-->
    <div class="tab-content">
        <!--begin:::Tab pane-->
        <div class="tab-pane fade show active" id="kt_overview_tab" role="tabpanel">
            <div class="card">
                <!--begin::Card header-->
                <div class="card-header">
                    <!--begin::Card title-->
                    <div class="card-title">
                        <h3>Genel Bakış</h3>
                    </div>
                    <!--end::Card title-->
                    <!--begin::Toolbar-->
                    <div class="card-toolbar">
                        <a href="{{ route('admin.ads.edit', ['id' => $ad->id]) }}"
                            class="btn btn-primary btn-sm d-flex flex-center">
                            <i class="fa fa-edit"></i> Düzenle
                        </a>
                    </div>
                    <!--end::Toolbar-->
                </div>
                <!--end::Card header-->

                <!--begin::Card body-->
                <div class="card-body p-9">
                    <h6>Genel</h6>
                    <div class="separator separator-dashed my-5"></div>
                    <!--begin::Row-->
                    <div class="row mb-7">
                        <!--begin::Label-->
                        <label class="col-lg-4 fw-semibold text-muted">Başlık</label>
                        <!--end::Label-->
                        <!--begin::Col-->
                        <div class="col-lg-8">
                            <span class="fw-bold fs-6 text-gray-800">{{ $ad->title }}</span>
                        </div>
                        <!--end::Col-->
                    </div>
                    <!--end::Row-->
                    <!--begin::Input group-->
                    <div class="row mb-7">
                        <!--begin::Label-->
                        <label class="col-lg-4 fw-semibold text-muted">Reklam Yeri</label>
                        <!--end::Label-->
                        <!--begin::Col-->
                        <div class="col-lg-8 fv-row">
                            <span
                                class="fw-semibold text-gray-800 fs-6">{{ implode(', ', $ad->placements->pluck('name')->toArray()) }}</span>
                        </div>
                        <!--end::Col-->
                    </div>
                    <!--end::Input group-->
                    <!--begin::Input group-->
                    <div class="row mb-7">
                        <!--begin::Label-->
                        <label class="col-lg-4 fw-semibold text-muted">Reklam Açıklaması</label>
                        <!--end::Label-->
                        <!--begin::Col-->
                        <div class="col-lg-8 fv-row">
                            <span class="fw-semibold text-gray-800 fs-6">{!! nl2br($ad->description) !!}</span>
                        </div>
                        <!--end::Col-->
                    </div>
                    <!--end::Input group-->
                    <!--begin::Input group-->
                    <div class="row mb-7">
                        <!--begin::Label-->
                        <label class="col-lg-4 fw-semibold text-muted">Gösterim Başlangıç Tarihi</label>
                        <!--end::Label-->
                        <!--begin::Col-->
                        <div class="col-lg-8 fv-row">
                            <span class="fw-semibold text-gray-800 fs-6">{{ $ad->get_start_date }}</span>
                        </div>
                        <!--end::Col-->
                    </div>
                    <!--end::Input group-->
                    <!--begin::Input group-->
                    <div class="row mb-7">
                        <!--begin::Label-->
                        <label class="col-lg-4 fw-semibold text-muted">Gösterim Saatleri</label>
                        <!--end::Label-->
                        <!--begin::Col-->
                        <div class="col-lg-8 fv-row">
                            <span class="fw-semibold text-gray-800 fs-6">{{ $ad->show_start_time->format('H:i') }} -
                                {{ $ad->show_end_time->format('H:i') }}</span>
                        </div>
                        <!--end::Col-->
                    </div>
                    <!--end::Input group-->

                    <h6>Hedefleme Kriterleri</h6>
                    <div class="separator separator-dashed my-5"></div>
                    <!--begin::Input group-->
                    <div class="row mb-7">
                        <!--begin::Label-->
                        <label class="col-lg-4 fw-semibold text-muted">Ülke</label>
                        <!--end::Label-->
                        <!--begin::Col-->
                        <div class="col-lg-8 fv-row">
                            <span class="fw-semibold text-gray-800 fs-6">{{ $ad?->target_country?->native }}</span>
                        </div>
                        <!--end::Col-->
                    </div>
                    <!--end::Input group-->
                    <!--begin::Input group-->
                    <div class="row mb-7">
                        <!--begin::Label-->
                        <label class="col-lg-4 fw-semibold text-muted">Şehir</label>
                        <!--end::Label-->
                        <!--begin::Col-->
                        <div class="col-lg-8 fv-row">
                            <span
                                class="fw-semibold text-gray-800 fs-6">{{ $ad?->target_cities && $ad->target_cities->isNotEmpty() ? implode(', ', $ad?->target_cities?->pluck('name')->toArray() ?? []) : 'Tümü' }}</span>
                        </div>
                        <!--end::Col-->
                    </div>
                    <!--end::Input group-->
                    <!--begin::Input group-->
                    <div class="row mb-7">
                        <!--begin::Label-->
                        <label class="col-lg-4 fw-semibold text-muted">Yaş</label>
                        <!--end::Label-->
                        <!--begin::Col-->
                        <div class="col-lg-8 fv-row">
                            <span
                                class="fw-semibold text-gray-800 fs-6">{{ $ad?->target_age_ranges && $ad->target_age_ranges->isNotEmpty() ? implode(', ', $ad?->target_age_ranges?->pluck('name')->toArray() ?? []) : 'Tümü' }}</span>
                        </div>
                        <!--end::Col-->
                    </div>
                    <!--end::Input group-->
                    <!--begin::Input group-->
                    <div class="row mb-7">
                        <!--begin::Label-->
                        <label class="col-lg-4 fw-semibold text-muted">Cinsiyet</label>
                        <!--end::Label-->
                        <!--begin::Col-->
                        <div class="col-lg-8 fv-row">
                            <span
                                class="fw-semibold text-gray-800 fs-6">{{ $ad?->target_genders && $ad->target_genders->isNotEmpty() ? implode(', ', $ad?->target_genders?->pluck('name')->toArray() ?? []) : 'Tümü' }}</span>
                        </div>
                        <!--end::Col-->
                    </div>
                    <!--end::Input group-->
                    <!--begin::Input group-->
                    <div class="row mb-7">
                        <!--begin::Label-->
                        <label class="col-lg-4 fw-semibold text-muted">Dil</label>
                        <!--end::Label-->
                        <!--begin::Col-->
                        <div class="col-lg-8 fv-row">
                            <span class="fw-semibold text-gray-800 fs-6">{{ $ad?->target_language?->name }}</span>
                        </div>
                        <!--end::Col-->
                    </div>
                    <!--end::Input group-->
                    <!--begin::Input group-->
                    <div class="row mb-7">
                        <!--begin::Label-->
                        <label class="col-lg-4 fw-semibold text-muted">Takım</label>
                        <!--end::Label-->
                        <!--begin::Col-->
                        <div class="col-lg-8 fv-row">
                            <span
                                class="fw-semibold text-gray-800 fs-6">{{ $ad?->target_teams && $ad->target_teams->isNotEmpty() ? implode(', ', $ad?->target_teams?->pluck('name')->toArray() ?? []) : 'Tümü' }}</span>
                        </div>
                        <!--end::Col-->
                    </div>
                    <!--end::Input group-->
                    <!--begin::Input group-->
                    <div class="row mb-7">
                        <!--begin::Label-->
                        <label class="col-lg-4 fw-semibold text-muted">İşletim Sitemi</label>
                        <!--end::Label-->
                        <!--begin::Col-->
                        <div class="col-lg-8 fv-row">
                            <span
                                class="fw-semibold text-gray-800 fs-6">{{ $ad->target_oses && $ad->target_oses->isNotEmpty() ? implode(', ', $ad?->target_oses?->pluck('name')->toArray() ?? []) : 'Tümü' }}</span>
                        </div>
                        <!--end::Col-->
                    </div>
                    <!--end::Input group-->
                </div>
                <!--end::Card body-->
            </div>
        </div>
        <!--end:::Tab pane-->

        <!--begin:::Tab pane-->
        <div class="tab-pane fade" id="kt_stats_tab" role="tabpanel">
            <!--begin::Card-->
            <div class="card pt-4 mb-6 mb-xl-9">
                <!--begin::Card header-->
                <div class="card-header border-0">
                    <!--begin::Card title-->
                    <div class="card-title">
                        <h2>Değerlendirme</h2>
                    </div>
                    <!--end::Card title-->
                    <!--begin::Toolbar-->
                    <div class="card-toolbar">
                        <div style="width: 215px;">
                            <x-admin.form-elements.date-range-picker customAttr="data-stats-filter=date_range"
                                customClass="form-control-sm" />
                        </div>
                    </div>
                    <!--end::Toolbar-->
                </div>
                <!--end::Card header-->

                <!--begin::Card body-->
                <div class="card-body position-relative loading" data-content-area="stats">
                    <div data-loading style="display: none;">
                        <span class="spinner-border spinner-border-sm align-middle text-primary border-2"></span>
                    </div>
                    <!--begin::Stats-->
                    <div class="row g-3">
                        <div class="col-xl-4">
                            <div class="card card-dashed flex-center min-w-175px p-6">
                                <span class="fs-4 fw-semibold text-success pb-1 px-2">Gösterim Sayısı</span>
                                <span class="fs-lg-2tx fw-bold" data-stats-value="total_impressions">0</span>
                            </div>
                        </div>
                        <div class="col-xl-4">
                            <div class="card card-dashed flex-center min-w-175px p-6">
                                <span class="fs-4 fw-semibold text-success pb-1 px-2">Tıklanma Sayısı</span>
                                <span class="fs-lg-2tx fw-bold" data-stats-value="total_clicks">0</span>
                            </div>
                        </div>
                        <div class="col-xl-4">
                            <div class="card card-dashed flex-center min-w-175px p-6">
                                <span class="fs-4 fw-semibold text-success pb-1 px-2">Tıklanma Oranı</span>
                                <span class="fs-lg-2tx fw-bold">
                                    <span data-stats-value="click_rate"></span>%
                                </span>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="card card-dashed flex-center min-w-175px p-6">
                                <span class="fs-4 fw-semibold text-success pb-1 px-2">Tamamını İzleyen Sayısı</span>
                                <span class="fs-lg-2tx fw-bold" data-stats-value="total_completed_views">0</span>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="card card-dashed flex-center min-w-175px p-6">
                                <span class="fs-4 fw-semibold text-success pb-1 px-2">Adil İzlenme Sayısı</span>
                                <span class="fs-lg-2tx fw-bold" data-stats-value="total_fair_views">0</span>
                            </div>
                        </div>
                    </div>
                    <!--end::Stats-->
                </div>
                <!--end::Card body-->
            </div>
            <!--end::Card-->
        </div>
        <!--end:::Tab pane-->
    </div>
    <!--end:::Tab content-->


    <!--start::Modals-->
    <x-admin.modals.index id='editAdStatusModal' title="Reklam Durumu Düzenle">
        <form id="editAdStatusForm" class="row g-5" action="{{ route('admin.ads.status-update', ['id' => $ad->id]) }}">
            @csrf
            <div class="col-xl-12">
                <label class="form-label">Durum</label>
                <x-admin.form-elements.ad-status-select name="status_id" :selectedOption="$ad->status_id"
                    dropdownParent="#editAdStatusModal" :hideSearch="true" />
            </div>
        </form>

        <x-slot:footer>
            <x-admin.form-elements.submit-btn>Değişiklikleri Kaydet</x-admin.form-elements.submit-btn>
        </x-slot:footer>
    </x-admin.modals.index>
    <x-admin.modals.index id='editAdPaymentStatusModal' title="Reklam Ödeme Durumu Düzenle">
        <form id="editAdPaymentStatusForm" class="row g-5"
            action="{{ route('admin.ads.payment-status-update', ['id' => $ad->id]) }}">
            @csrf
            <div class="col-xl-12">
                <label class="form-label">Ödeme Tutarı</label>
                <input type="text" value="{{ $ad->drawTotalBudget }}" class="form-control text-gray-600" disabled>
            </div>
            <div class="col-xl-12">
                <label class="form-label">Ödeme Durum</label>
                <x-admin.form-elements.ad-payment-status-select name="payment_status_id" :selectedOption="$ad->payment_status_id"
                    dropdownParent="#editAdPaymentStatusModal" :hideSearch="true" />
            </div>
        </form>

        <x-slot:footer>
            <x-admin.form-elements.submit-btn>Değişiklikleri Kaydet</x-admin.form-elements.submit-btn>
        </x-slot:footer>
    </x-admin.modals.index>
    <!--end::Modals-->
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            $('#viewsDataTable').dataTable();
        });
    </script>
    <!-- start::Edit Ad Status-->
    <script>
        $(document).ready(function() {
            const form = $("#editAdStatusForm"),
                modal = $("#editAdStatusModal");

            $(document).on('click', '.editAdStatusButton', function() {
                modal.modal('show');
            });

            $(document).on('click', '#editAdStatusModal button[type="submit"]', function() {
                $('#editAdStatusForm').submit();
            })
            $(document).on("submit", "#editAdStatusForm", function(e) {
                e.preventDefault();

                let formData = new FormData(this),
                    form = $(this),
                    submitButton = modal.find("[type='submit']");

                $.ajax({
                    type: 'POST',
                    url: form.attr('action'),
                    data: formData,
                    dataType: 'json',
                    contentType: false,
                    processData: false,
                    cache: false,
                    beforeSend: function() {
                        propSubmitButton(submitButton, 1);
                    },
                    success: function(res) {
                        swal.success({
                            message: res.message
                        }).then(() => window.location.reload())
                    },
                    error: function(xhr) {
                        swal.error({
                            message: xhr.responseJSON?.message ?? null
                        })
                    },
                    complete: function() {
                        propSubmitButton(submitButton, 0);
                    }
                })
            })
        });
    </script>
    <!-- end::Edit Ad Status-->

    <!-- start::Edit Ad Payment Status-->
    <script>
        $(document).ready(function() {
            const form = $("#editAdPaymentStatusForm"),
                modal = $("#editAdPaymentStatusModal");

            $(document).on('click', '.editAdPaymentStatusButton', function() {
                modal.modal('show');
            });

            $(document).on('click', '#editAdPaymentStatusModal button[type="submit"]', function() {
                $('#editAdPaymentStatusForm').submit();
            })
            $(document).on("submit", "#editAdPaymentStatusForm", function(e) {
                e.preventDefault();

                let formData = new FormData(this),
                    form = $(this),
                    submitButton = modal.find("[type='submit']");

                $.ajax({
                    type: 'POST',
                    url: form.attr('action'),
                    data: formData,
                    dataType: 'json',
                    contentType: false,
                    processData: false,
                    cache: false,
                    beforeSend: function() {
                        propSubmitButton(submitButton, 1);
                    },
                    success: function(res) {
                        swal.success({
                            message: res.message
                        }).then(() => window.location.reload())
                    },
                    error: function(xhr) {
                        swal.error({
                            message: xhr.responseJSON?.message ?? null
                        })
                    },
                    complete: function() {
                        propSubmitButton(submitButton, 0);
                    }
                })
            })
        });
    </script>
    <!-- end::Edit Ad Payment Status-->

    <!-- start::Stats-->
    <script>
        const statsContentArea = $('[data-content-area="stats"]');

        const setLoading = (status, element) => {
            if (!element) {
                element = $('[data-content-area]')
            }
            if (status) {
                element?.addClass('loading');
                element?.find('[data-loading]').show();
            } else {
                element?.removeClass('loading');
                element?.find('[data-loading]').hide();
            }
        };

        const fetchStatsData = () => {
            let dateRangePicker = $('[data-stats-filter="date_range"]').data('daterangepicker'),
                startDate = dateRangePicker?.startDate.format('YYYY-MM-DD'),
                endDate = dateRangePicker?.endDate.format('YYYY-MM-DD');

            $.ajax({
                url: "{{ route('admin.ads.get-stats-data') }}",
                method: 'GET',
                data: {
                    ad_id: {{ $ad->id }},
                    start_date: startDate,
                    end_date: endDate
                },
                beforeSend: function() {
                    setLoading(true, statsContentArea);
                },
                success: function(res) {
                    $('[data-stats-value="total_impressions"]').text(formatNumber(res?.total_impressions ?? 0, 'dot'));
                    $('[data-stats-value="total_clicks"]').text(formatNumber(res?.total_clicks ?? 0, 'dot'));
                    $('[data-stats-value="click_rate"]').text(res?.click_rate ?? 0);
                    $('[data-stats-value="total_completed_views"]').text(formatNumber(res?.total_completed_views ?? 0, 'dot'));
                    $('[data-stats-value="total_fair_views"]').text(formatNumber(res?.total_fair_views ?? 0, 'dot'));
                },
                error: function(xhr) {
                    swal.error({
                        message: xhr.responseJSON?.message ?? null
                    })
                },
                complete: function() {
                    setLoading(false, statsContentArea);
                }
            });
        }


        $(document).ready(function() {
            fetchStatsData();

            $(document).on('change', '[data-stats-filter]', function() {
                fetchStatsData();
            });
        });
    </script>
    <!-- end::Stats-->
@endsection
