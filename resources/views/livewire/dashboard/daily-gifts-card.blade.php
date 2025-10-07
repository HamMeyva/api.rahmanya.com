<div class="card card-flush h-md-50 mb-5 mb-xl-10" wire:init="loadData">
    <!--begin::Card body-->
    <div class="card-body d-flex flex-column justify-content-between @if (is_null($count)) loading @endif"
        wire:loading.class="loading">
        <div>
            <!--begin::Info-->
            <div class="d-flex align-items-center">
                <!--begin::Amount-->
                <span>
                    <span
                        class="fs-2hx fw-bold text-gray-900 me-2 lh-1 ls-n2">{{ (new App\Helpers\CommonHelper())->formatNumber($this->count ?? 0) }} Adet</span>
                </span>
                <!--end::Amount-->
            </div>
            <!--end::Info-->
            <!--begin::Subtitle-->
            <span class="text-gray-500 pt-1 fw-semibold fs-6">Bugün Gönderilen Hediye Sayısı</span>
            <!--end::Subtitle-->
        </div>

        <div>
            <!--begin::Info-->
            <div class="d-flex align-items-center">
                <!--begin::Currency-->
                <span class="fs-4 fw-semibold text-gray-500 me-1 align-self-start">₺</span>
                <!--end::Currency-->
                <!--begin::Amount-->
                <span
                    class="fs-2hx fw-bold text-gray-900 me-2 lh-1 ls-n2">{{ number_format($this->amount ?? 0, 2, ',', '.') }}</span>
                <!--end::Amount-->
            </div>
            <!--end::Info-->
            <!--begin::Subtitle-->
            <span class="text-gray-500 pt-1 fw-semibold fs-6">Bugün Gönderilen Hediye Tutarı</span>
            <!--end::Subtitle-->
        </div>

        <div>
            <!--begin::Progress-->
            <div class="d-flex align-items-center flex-column mt-3 w-100 pb-4">
                <div class="d-flex justify-content-between w-100 mt-auto mb-2">
                    <span class="fw-bolder fs-6 text-gray-900">Hedefe kalan {{ $this->remainingGiftsToTarget }} hediye</span>
                    <!--Hedefi 1000 olarak alalım-->
                    <span class="fw-bold fs-6 text-gray-500">{{ $this->remainingGiftsToTargetPercentage }}%</span>
                </div>
                <div class="h-8px mx-3 w-100 bg-light-success rounded">
                    <div class="bg-success rounded h-8px" role="progressbar" style="width: {{ $this->remainingGiftsToTargetPercentage }}%;" aria-valuenow="50"
                        aria-valuemin="0" aria-valuemax="100"></div>
                </div>
            </div>
            <!--end::Progress-->
        </div>
    </div>
    <!--end::Card body-->
</div>
