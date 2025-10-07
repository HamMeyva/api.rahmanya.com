<div class="card card-flush h-xl-100" wire:init="loadData">
    <!--begin::Card header-->
    <div class="card-header pt-7">
        <!--begin::Title-->
        <h3 class="card-title align-items-start flex-column">
            <span class="card-label fw-bold text-gray-900">Coin Paketi Satışları</span>
            <span class="text-gray-500 mt-1 fw-semibold fs-6">Son 10 Kayıt</span>
        </h3>
        <!--end::Title-->
    </div>
    <!--end::Card header-->
    <!--begin::Card body-->
    <div class="card-body {{ $isLoading ? 'loading' : '' }}">
        <!--begin::Table-->
        <table class="table align-middle table-hover table-row-dashed fs-6 gy-3">
            <!--begin::Table head-->
            <thead>
                <!--begin::Table row-->
                <tr class="gs-0 fw-bold fs-5 text-gray-900">
                    <th class="min-w-150px ps-2">Shoot Coin</th>
                    <th class="min-w-150px">Tutar</th>
                    <th class="">Kullanıcı</th>
                    <th class="">Ödeme</th>
                    <th class="">İşlem Tarihi</th>
                </tr>
                <!--end::Table row-->
            </thead>
            <!--end::Table head-->
            <!--begin::Table body-->
            <tbody class="fw-bold text-gray-700">
                @if (count($payments) <= 0)
                    <tr>
                        <td colspan="5 " class="text-center pt-10">Veri bulunamadı</td>
                    </tr>
                @else
                    @foreach ($payments as $payment)
                        <tr class="fw-semibold fs-7 text-gray-900">
                            <td class="ps-2">{!! $payment->payable_data['coin_amount'] ?? '<em>Bilinmeyen</em>' !!} Adet</td>
                            <td>{{ $payment->draw_total_amount }}</td>
                            <td><a href="{{ route('admin.users.show', ['id' => $payment?->user?->id]) }}" target="_blank" class="text-gray-800 text-hover-primary">{!! $payment?->user?->nickname ?? '<em>Bilinmeyen</em>' !!}</a></td>
                            <td><span class="badge badge-secondary">{{ $payment->get_channel }}</span></td>
                            <td><span class="badge badge-secondary">{{ $payment->get_created_at }}</span></td>
                        </tr>
                    @endforeach
                @endif
            </tbody>
            <!--end::Table body-->
        </table>
        <!--end::Table-->
    </div>
    <!--end::Card body-->
</div>
