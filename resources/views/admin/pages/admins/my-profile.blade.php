@extends('admin.template')
@section('title', 'Profilim')
@section('breadcrumb')
    <x-admin.breadcrumb data="Profilim" />
@endsection
@section('master')
    <div class="card mb-6">
        <div class="card-body pt-9 pb-0">
            <!--begin::Details-->
            <div class="d-flex flex-wrap flex-sm-nowrap">
                <!--begin: Pic-->
                <div class="me-7 mb-4">
                    <div class="symbol symbol-100px symbol-fixed position-relative">
                        <div data-bs-toggle="tooltip" title=""
                            class="symbol-label fs-3 bg-light-primary text-primary text-center"
                            data-bs-original-title="{{ $admin->full_name }}">
                            <span class="fs-3x">{{ $admin->first_name[0] . $admin->last_name[0] }}</span>
                        </div>
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
                                <a href="#"
                                    class="text-gray-900 text-hover-primary fs-2 fw-bold me-1">{{ $admin->full_name }}</a>
                            </div>
                            <!--end::Name-->

                            <!--begin::Info-->
                            <div class="d-flex flex-wrap fw-semibold fs-6 mb-4 pe-2">
                                <a href="#"
                                    class="d-flex align-items-center text-gray-500 text-hover-primary me-5 mb-2">
                                    <i class="fa fa-shield fs-7 me-1 text-gray-400"></i>
                                    {{ $admin->roles->pluck('name')->implode(', ') }}
                                </a>
                                <a href="#" class="d-flex align-items-center text-gray-500 text-hover-primary mb-2">
                                    <i class="ki-duotone ki-sms fs-4 me-1"><span class="path1"></span><span
                                            class="path2"></span></i> {{ $admin->email }}
                                </a>
                            </div>
                            <!--end::Info-->
                        </div>
                        <!--end::User-->

                        <!--begin::Actions-->
                        <div class="d-flex my-4">

                        </div>
                        <!--end::Actions-->
                    </div>
                    <!--end::Title-->
                </div>
                <!--end::Info-->
            </div>
            <!--end::Details-->

            <!--begin::Navs-->
            <ul class="nav nav-stretch nav-line-tabs nav-line-tabs-2x border-transparent fs-5 fw-bold">
                <!--begin::Nav item-->
                <li class="nav-item mt-2">
                    <a class="nav-link text-active-primary ms-0 me-10 py-5 active" data-bs-toggle="tab"
                        href="#kt_admin_view_overview_tab">Genel Bakış</a>
                </li>
                <!--end::Nav item-->
                <!--begin::Nav item-->
                <li class="nav-item mt-2">
                    <a class="nav-link text-active-primary ms-0 me-10 py-5" data-bs-toggle="tab"
                        href="#kt_admin_view_notifications_tab">Bildirimler</a>
                </li>
                <!--end::Nav item-->
            </ul>
            <!--begin::Navs-->
        </div>
    </div>

    <!--begin:::Tab content-->
    <div class="tab-content" id="myTabContent">
        <!--begin:::Tab pane-->
        <div class="tab-pane fade show active" id="kt_admin_view_overview_tab" role="tabpanel">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Genel Bakış</h4>
                </div>
                <div class="card-body">
                    <!--begin::Row-->
                    <div class="row mb-7">
                        <!--begin::Label-->
                        <label class="col-lg-4 fw-bold text-muted">Ad-Soyad</label>
                        <!--end::Label-->
                        <!--begin::Col-->
                        <div class="col-lg-8">
                            <span class="fw-bolder fs-6 text-gray-800">{{ $admin->full_name }}</span>
                        </div>
                        <!--end::Col-->
                    </div>
                    <!--end::Row-->
                    <!--begin::Input group-->
                    <div class="row mb-7">
                        <!--begin::Label-->
                        <label class="col-lg-4 fw-bold text-muted">E-Posta Adresi</label>
                        <!--end::Label-->
                        <!--begin::Col-->
                        <div class="col-lg-8 fv-row">
                            <span class="fw-bolder text-gray-800 fs-6" id="overviewEmail">{{ $admin->email }}</span>
                        </div>
                        <!--end::Col-->
                    </div>
                    <!--end::Input group-->
                    <!--begin::Input group-->
                    <div class="row mb-7">
                        <!--begin::Label-->
                        <label class="col-lg-4 fw-bold text-muted">Katılım Tarihi</label>
                        <!--end::Label-->
                        <!--begin::Col-->
                        <div class="col-lg-8">
                            <label class="fw-bold fs-6 text-gray-800">{{ $admin?->get_created_at ?? '-' }}</label>
                        </div>
                        <!--end::Col-->
                    </div>
                    <!--end::Input group-->
                </div>
            </div>
        </div>
        <!--end:::Tab pane-->
        <!--begin:::Tab pane-->
        <div class="tab-pane fade" id="kt_admin_view_notifications_tab" role="tabpanel">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Bildirimler</h4>
                </div>
                <div class="card-body">
                    @livewire('notification-list')
                </div>
            </div>
        </div>
        <!--end:::Tab pane-->
    </div>
    <!--end:::Tab content-->
@endsection
@section('scripts')
    <script>
        $(document).ready(function() {
            const urlParams = new URLSearchParams(window.location.search);
            const tab = urlParams.get('tab');

            if (tab === 'notifications') {
                $('a[href="#kt_admin_view_notifications_tab"]').tab('show');
            }
            //...
        });
    </script>
@endsection
