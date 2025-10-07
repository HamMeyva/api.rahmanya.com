@extends('admin.template')

@use('App\Models\Common\Country')
@use('App\Models\Common\Currency')

@section('title', 'Shoot Coin Çekim Fiyatları')
@section('breadcrumb')
    <x-admin.breadcrumb data="Shoot Coin Çekim Fiyatları" />
@endsection
@section('master')
    <!--begin::Card-->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <h3>Shoot Coin Çekim Fiyatları Listesi</h3>
            </div>
        </div>
        <form id="primaryForm" class="card-body">
            @csrf
            <div class="alert alert-danger mb-9">
                Shoot Coin çekim talepleri sırasında her bir para birimininin ne kadardan paraya dönüştürlecegini hesaplarken bu fiyatlara göre hesaplanacaktır.
            </div>
            @foreach ($prices as $item)
                <div class="row mb-3">
                    <div class="col-xl-6">
                        <input class="form-control" value="{{ $item->currency->name }}" readonly>
                        <input type="hidden" class="form-control" name="prices[{{ $item->id }}][currency_id]" value="{{ $item->currency_id }}" readonly>
                    </div>
                    <div class="col-xl-6">
                        <input class="form-control price-input" name="prices[{{ $item->id }}][coin_unit_price]" value="{{ $item->get_coin_unit_price }}">
                    </div>
                </div>
            @endforeach

            <div class="col-12 text-end">
                <x-admin.form-elements.submit-btn>Kaydet</x-admin.form-elements.submit-btn>
            </div>
        </form>
    </div>
    <!--end::Card-->
@endsection
@section('scripts')
    <script>
        $(document).ready(function() {
            $(document).on("submit", "#primaryForm", function(e) {
                e.preventDefault();
                let formData = new FormData(this),
                    btn = $(this).find('[type="submit"]');

                $.ajax({
                    type: 'POST',
                    url: "{{ route('admin.coin-withdrawal-prices.bulk-update') }}",
                    data: formData,
                    dataType: 'json',
                    contentType: false,
                    processData: false,
                    cache: false,
                    beforeSend: function() {
                        propSubmitButton(btn, 1);
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
                        propSubmitButton(btn, 0);
                    }
                })
            })
        })
    </script>
@endsection
