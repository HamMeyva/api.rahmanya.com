@extends('admin.template')
@use('App\Models\Coupon')
@use('App\Models\Common\Country')
@use('App\Models\Common\Currency')
@section('title', isset($coupon) ? $coupon->code : 'Kupon Kodu Ekle')
@section('breadcrumb')
<x-admin.breadcrumb :data="isset($coupon) ? $coupon->code : 'Kupon Kodu Ekle'" :backUrl="route('admin.coupons.index')" />
@endsection
@section('master')
<form id="primaryForm" class="card"
    action="{{ isset($coupon) ? route('admin.coupons.update', ['coupon' => $coupon->id]) : route('admin.coupons.store') }}">
    @csrf
    <div class="card-body row g-4">
        <div class="col-xl-12 mb-3">
            <label class="form-check form-switch form-check-custom form-check-solid">
                <input class="form-check-input"
                    name="is_active"
                    type="checkbox"
                    {{isset($coupon) ? ($coupon->is_active ? 'checked="checked"' : null) : 'checked="checked"'}}
                    value="1">
                <span class="form-check-label fw-semibold text-muted">
                    Durum
                </span>
            </label>
        </div>
        <div class="col-xl-6">
            <label class="fs-6 fw-semibold mb-2 required">Kupon Kodu</label>
            <input type="text"
                class="form-control"
                placeholder="MERT-10"
                value="{{$coupon->code ?? null}}"
                name="code">
        </div>
        <div class="col-xl-6">
            <label class="fs-6 fw-semibold mb-2 required">İndirim Tipi</label>
            <x-admin.form-elements.coupon-discount-type-select :selectedOption="$coupon->discount_type ?? Coupon::DISCOUNT_TYPE_PERCENTAGE" name="discount_type" :hideSearch="true" />
        </div>
        <div class="col-xl-6">
            <label class="fs-6 fw-semibold mb-2 required">Ülke</label>
            <x-admin.form-elements.country-select :selectedOption="$coupon->country_id ?? Country::where('iso2', 'TR')->first()?->id ?? null" name="country_id" />
        </div>
        <div class="col-xl-6">
            <label class="fs-6 fw-semibold mb-2 required">İndirim</label>
            <div class="input-group mb-5">
                <span class="input-group-text">%</span>
                <input type="number" placeholder="10" class="form-control" value="{{$coupon->discount_amount ?? null}}" name="discount_amount" min="1" step="1">
            </div>
        </div>
        <div class="col-xl-6">
            <label class="fs-6 fw-semibold mb-2 required">Başlangıç Tarihi</label>
            <x-admin.form-elements.date-time-input name="start_date"
                :value="$coupon->start_date ?? null" />
        </div>
        <div class="col-xl-6">
            <label class="fs-6 fw-semibold mb-2 required">Bitiş Tarihi</label>
            <x-admin.form-elements.date-time-input name="end_date"
                :value="$coupon->end_date ?? null" />
        </div>
        <div class="col-xl-12">
            <label class="fs-6 fw-semibold mb-2 required">Maks Kullanım</label>
            <input type="number"
                class="form-control"
                placeholder="100"
                value="{{$coupon->max_usage ?? null}}"
                name="max_usage">
        </div>
        <div class="col-12 mt-10">
            <x-admin.form-elements.submit-btn
                class="w-100 submit-btn">{{isset($coupon) ? 'Değişiklikleri Kaydet' : 'Ekle'}}</x-admin.form-elements.submit-btn>
        </div>
    </div>
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
                    }).then(() => window.location.href = res?.redirect)
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

        const discountTypeSelect = $('[name="discount_type"]'),
            discountAmountInput = $('[name="discount_amount"]'),
            countrySelect = $('[name="country_id"]');

        $(document).on('change', '[name="discount_type"]', function() {
            const discountType = $(this).val();
            const countryParams = getCountryParams();

            if (discountType == '{{ Coupon::DISCOUNT_TYPE_FIXED }}') {
                discountAmountInput.parent().find('.input-group-text').text(countryParams?.currency_symbol ?? null);
            } else {
                discountAmountInput.parent().find('.input-group-text').text('%');
            }
        })

        $(document).on('change', '[name="country_id"]', function() {
            const params = getCountryParams();
            const discountType = discountTypeSelect.val();

            if (discountType == '{{ Coupon::DISCOUNT_TYPE_FIXED }}') {
                discountAmountInput.parent().find('.input-group-text').text(params?.currency_symbol ?? null);
            } else {
                discountAmountInput.parent().find('.input-group-text').text('%');
            }
        })

        const systemCurrenySymbols = '@json(Currency::pluck("symbol")->toArray())';
        const getCountryParams = () => {
            let selectedOption = countrySelect.find('option:selected');
            if (!selectedOption || !selectedOption.data('extra-params')) return null;

            let params = JSON.parse(atob(selectedOption.data('extra-params')));

            if (!systemCurrenySymbols.includes(params.currency_symbol)) {
                params.currency_symbol = '$';
            }

            return params;
        }
    })
</script>
@endsection