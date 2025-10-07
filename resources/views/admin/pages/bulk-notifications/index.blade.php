@extends('admin.template')
@section('title', 'Toplu Bildirimler')
@section('breadcrumb')
<x-admin.breadcrumb data="Toplu Bildirimler" />
@endsection
@section('master')
<form id="primaryForm" class="card">
    @csrf
    <div class="card-body row g-6">
        <div class="col-xl-6">
            <label class="form-label required">Ülke</label>
            <x-admin.form-elements.country-select name="country_id" />
        </div>
        <div class="col-xl-6">
            <label class="form-label">Şehir</label>
            <x-admin.form-elements.city-select name="city_ids[]"
                placeholder="Tümü"
                customAttr="multiple" />
        </div>
        <div class="col-xl-12">
            <label class="form-label">Takım</label>
            <x-admin.form-elements.team-select name="team_id"
                placeholder="Tümü" />
        </div>
        <div class="col-xl-12" data-kt-buttons="true">
            <div class="row">
                <div class="col-xl-4">
                    <!--begin::Option-->
                    <label
                        class="btn btn-outline btn-outline-dashed btn-active-light-primary d-flex text-start p-6 mb-6 active"
                        data-type="sms">
                        <!--begin::Input-->
                        <input class="btn-check" type="radio" checked="checked" name="source_type" value="1" />
                        <!--end::Input-->
                        <!--begin::Label-->
                        <span class="d-flex">
                            <!--begin::Icon-->
                            <i class="ki-duotone ki-messages fs-2hx">
                                <span class="path1"></span>
                                <span class="path2"></span>
                                <span class="path3"></span>
                                <span class="path4"></span>
                                <span class="path5"></span>
                            </i>
                            <!--end::Icon-->
                            <!--begin::Info-->
                            <span class="ms-4 d-flex align-items-center">
                                <span class="fs-3 fw-bold text-gray-900 d-block">SMS</span>
                            </span>
                            <!--end::Info-->
                        </span>
                        <!--end::Label-->
                    </label>
                    <!--end::Option-->
                </div>
                <div class="col-xl-4">
                    <!--begin::Option-->
                    <label
                        class="btn btn-outline btn-outline-dashed btn-active-light-primary d-flex text-start p-6"
                        data-type="email">
                        <!--begin::Input-->
                        <input class="btn-check" type="radio" name="source_type" value="2" />
                        <!--end::Input-->

                        <!--begin::Label-->
                        <span class="d-flex">
                            <!--begin::Icon-->
                            <i class="ki-duotone ki-sms fs-2hx">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            <!--end::Icon-->

                            <!--begin::Info-->
                            <span class="ms-4 d-flex align-items-center">
                                <span class="fs-3 fw-bold text-gray-900 d-block">E-Posta</span>
                            </span>
                            <!--end::Info-->
                        </span>
                        <!--end::Label-->
                    </label>
                    <!--end::Option-->
                </div>
                <div class="col-xl-4">
                    <!--begin::Option-->
                    <label
                        class="btn btn-outline btn-outline-dashed btn-active-light-primary d-flex text-start p-6"
                        data-type="push">
                        <!--begin::Input-->
                        <input class="btn-check" type="radio" name="source_type" value="3" />
                        <!--end::Input-->

                        <!--begin::Label-->
                        <span class="d-flex">
                            <!--begin::Icon-->
                            <i class="ki-duotone ki-notification-on fs-2hx">
                                <span class="path1"></span>
                                <span class="path2"></span>
                                <span class="path3"></span>
                                <span class="path4"></span>
                                <span class="path5"></span>
                            </i>
                            <!--end::Icon-->

                            <!--begin::Info-->
                            <span class="ms-4 d-flex align-items-center">
                                <span class="fs-3 fw-bold text-gray-900 d-block">Anlık Bildirim</span>
                            </span>
                            <!--end::Info-->
                        </span>
                        <!--end::Label-->
                    </label>
                    <!--end::Option-->
                </div>
            </div>
        </div>
        <div class="col-xl-12 sms-area">
            <div class="row gap-5">
                <div class="col-xl-12">
                    <label class="form-label">Sms Metni</label>
                    <textarea name="sms[body]" class="form-control"></textarea>
                </div>
                <div class="col-xl-12">
                    <x-admin.form-elements.submit-btn
                        class="w-100 submit-btn">Sms Gönder
                    </x-admin.form-elements.submit-btn>
                </div>
            </div>
        </div>
        <div class="col-xl-12 email-area" style="display: none">
            <div class="row gap-5">
                <div class="col-xl-12">
                    <label class="form-label">Konu</label>
                    <input type="text" class="form-control" name="email[title]">
                </div>
                <div class="col-xl-12">
                    <label class="form-label">E-Posta Metni</label>
                    <textarea id="email_tinymce" name="email[body]"
                        class="tox-target"></textarea>
                </div>
                <div class="col-xl-12">
                    <x-admin.form-elements.submit-btn
                        class="w-100 submit-btn">E-Posta Gönder
                    </x-admin.form-elements.submit-btn>
                </div>
            </div>
        </div>
        <div class="col-xl-12 push-area" style="display: none">
            <div class="row gap-5">
                <div class="col-xl-12">
                    <label class="form-label">Başlık</label>
                    <input type="text" class="form-control" name="push[title]">
                </div>
                <div class="col-xl-12">
                    <label class="form-label">Bildirim Metni</label>
                    <textarea name="push[body]" class="form-control"></textarea>
                </div>
                <div class="col-xl-12">
                    <x-admin.form-elements.submit-btn
                        class="w-100 submit-btn">Anlık Bildirim Gönder
                    </x-admin.form-elements.submit-btn>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
@section('scripts')
<script src="{{assetAdmin('plugins/custom/tinymce/tinymce.bundle.js')}}"></script>
<script>
    tinymce.init({
        selector: "#email_tinymce",
        height: "480",
        toolbar: "advlist | autolink | link | lists charmap | print preview",
        plugins: "advlist autolink link lists charmap print preview",
    });

    $(document).ready(function() {
        const primaryForm = $('#primaryForm');
        const smsArea = $('.sms-area');
        const emailArea = $('.email-area');
        const pushArea = $('.push-area');
        let submitUrl = '{{route("admin.bulk-notifications.send-sms")}}';

        $(document).on('click', '[data-type="sms"]', function() {
            emailArea.hide();
            pushArea.hide();
            smsArea.show();

            submitUrl = '{{route("admin.bulk-notifications.send-sms")}}';
        })

        $(document).on('click', '[data-type="email"]', function() {
            smsArea.hide();
            pushArea.hide();
            emailArea.show();

            submitUrl = '{{route("admin.bulk-notifications.send-email")}}';
        })

        $(document).on('click', '[data-type="push"]', function() {
            smsArea.hide();
            emailArea.hide();
            pushArea.show();

            submitUrl = '{{route("admin.bulk-notifications.send-push")}}';
        })

        $(document).on('submit', '#primaryForm', function(e) {
            e.preventDefault()

            $.ajax({
                type: 'POST',
                url: submitUrl,
                data: new FormData(this),
                dataType: 'json',
                contentType: false,
                processData: false,
                cache: false,
                beforeSend: function() {
                    propSubmitButton(primaryForm.find("button[type='submit']"), 1);
                },
                success: function(res) {
                    swal.success({
                        message: res.message
                    }).then((r) => {
                        if (res?.redirect_url) {
                            window.location.href = res.redirect_url
                        } else {
                            window.location.reload()
                        }
                    })
                },
                error: function(xhr) {
                    swal.error({
                        message: xhr?.responseJSON?.message ?? null
                    })
                },
                complete: function() {
                    propSubmitButton(primaryForm.find("button[type='submit']"), 0);
                }
            })
        })
    })
</script>
@endsection