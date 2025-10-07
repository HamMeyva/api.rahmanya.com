@extends('admin.template')
@use('App\Models\AppSetting')
@section('title', 'Sistem Ayarları')
@section('breadcrumb')
<x-admin.breadcrumb data="Sistem Ayarları" />
@endsection
@section('master')
<!--begin::Card-->
<div class="card card-xxl-stretch mb-5 mb-xxl-10">
    <!--begin::Header-->
    <div class="card-header">
        <div class="card-title">
            <h3>Sistem Ayarları</h3>
        </div>
    </div>
    <!--end::Header-->
    <!--begin::Body-->
    <form id="updateAppSettingForm" class="card-body d-flex flex-column gap-7">
        @csrf
        @foreach ($appSettings as $item)
        <div class="row">
            <!--begin::Label-->
            <label class="col-lg-4 col-form-label required fw-semibold fs-6">{{ $item->get_label }}</label>
            <!--end::Label-->
            <!--begin::Col-->
            <div class="col-lg-8 d-flex align-items-center">
                @if ($item->type === AppSetting::TYPE_STRING)
                <input type="text" name="{{ $item->key }}" class="form-control form-control-lg"
                    placeholder="{{ $item->getLabel }}" value="{{ $item->value }}">
                @elseif($item->type === AppSetting::TYPE_TEXTAREA)
                <textarea name="{{ $item->key }}" rows="3" class="form-control" placeholder="{{ $item->getLabel }}">{{ $item->value }}</textarea>
                @elseif($item->type === AppSetting::TYPE_INTEGER)
                <input type="number" name="{{ $item->key }}" step="1"
                    onkeypress="if(event.key === '.' || event.key === ',') event.preventDefault();"
                    class="form-control form-control-lg" placeholder="{{ $item->getLabel }}"
                    value="{{ $item->value }}">
                @elseif($item->type === AppSetting::TYPE_TEXT_EDITOR)
                <textarea name="{{ $item->key }}" rows="3" class="form-control tinymce" placeholder="{{ $item->getLabel }}">{{ $item->value }}</textarea>
                @elseif($item->type === AppSetting::TYPE_BOOLEAN)
                <input type="hidden" name="{{ $item->key }}" value="0">
                <label class="form-check form-switch form-check-custom">
                    <input class="form-check-input" type="checkbox" value="1" name="{{ $item->key }}"
                        {{ $item->value === '1' ? 'checked' : '' }}>
                    <span class="form-check-label fw-semibold text-muted">Aktif</span>
                </label>
                @endif
            </div>
            <!--end::Col-->
        </div>
        @endforeach
        <div class="mt-5">
            <x-admin.form-elements.submit-btn id="updateAppSettingSubmitBtn" class="w-100">Değişiklikleri Kaydet</x-admin.form-elements.submit-btn>
        </div>
    </form>
    <!--end::Body-->
</div>
<!--end::Card-->
@endsection
@section('scripts')
<script src="{{ assetAdmin('plugins/custom/tinymce/tinymce.bundle.js') }}"></script>
<script>
    $(document).ready(function() {
        tinymce.init({
            selector: '.tinymce',
            plugins: 'advlist autolink lists link image charmap print preview anchor',
            toolbar: 'undo redo | formatselect | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | help'
        });

        $(document).on("submit", "#updateAppSettingForm", function(e) {
            e.preventDefault();
            let form = $(this);
            $.ajax({
                type: 'POST',
                url: '{{ route("admin.settings.app-settings.update") }}',
                data: new FormData(this),
                dataType: 'json',
                contentType: false,
                processData: false,
                cache: false,
                beforeSend: function() {
                    propSubmitButton($('#updateAppSettingSubmitBtn'), 1);
                },
                success: function(res) {
                    swal.success({
                        message: res.message
                    }).then((r) => {
                        window.location.reload()
                    })
                },
                error: function(xhr) {
                    swal.error({
                        message: xhr?.responseJSON?.message ?? null
                    })
                },
                complete: function() {
                    propSubmitButton($('#updateAppSettingSubmitBtn'), 0);
                }
            })
        })
    })
</script>
@endsection