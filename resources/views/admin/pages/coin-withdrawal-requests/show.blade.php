@extends('admin.template')
@use('App\Helpers\CommonHelper')
@use('App\Models\Coin\CoinWithdrawalRequest')
@use('Carbon\Carbon')
@use('App\Models\Morph\ReportProblem')
@section('title', "Çekim Talebi #{$withdrawalRequest->id}")
@section('breadcrumb')
    <x-admin.breadcrumb :data="[
        'Çekim Talebi #' . $withdrawalRequest->id,
        'Çekim Talepleri' => route('admin.coin-withdrawal-requests.index'),
    ]" :backUrl="route('admin.coin-withdrawal-requests.index')" />
@endsection
@section('master')
    <!--begin::Layout-->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <!--begin::Heading-->
                    <div class="d-flex align-items-center justify-content-between mb-12">
                        <div class="d-flex align-items-center">
                            <!--begin::Icon-->
                            <i class="ki-duotone ki-file-added fs-4qx ms-n2 me-3">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            <!--end::Icon-->
                            <!--begin::Content-->
                            <div class="d-flex flex-column">
                                <!--begin::Title-->
                                <h1>
                                    <a target="_blank" href="{{ $userProfileUrl }}"
                                        class="text-gray-800 fw-semibold text-hover-primary">{{ $withdrawalRequest->user->full_name }}</a>
                                </h1>
                                <!--end::Title-->
                                <!--begin::Info-->
                                <div class="">
                                    <!--begin::Label-->
                                    <span class="fw-semibold text-muted">Oluşturulma Tarihi
                                        <span
                                            class="fw-bold text-gray-600 me-1">{{ Carbon::parse($withdrawalRequest->created_at->format('Y-m-d H:i:s'))->diffForHumans() }}</span>({{ $withdrawalRequest->get_created_at }})</span>
                                    <!--end::Label-->
                                </div>
                                <!--end::Info-->
                            </div>
                            <!--end::Content-->
                        </div>
                        <div class="d-flex flex-center flex-column">
                            <div class="fw-bold fs-6 text-gray-800">
                                Talep Durumu
                                <div class="separator mb-1"></div>
                            </div>
                            <div>
                                <span
                                    class="badge badge-{{ $withdrawalRequest->get_status_color }} badge-lg px-3 py-3">{{ $withdrawalRequest->get_status }}</span>
                            </div>
                        </div>
                    </div>
                    <!--end::Heading-->
                    @if ($withdrawalRequest->status_id === CoinWithdrawalRequest::STATUS_APPROVED)
                        <div class="alert alert-success d-flex align-items-center gap-2 mb-0" style="color: #2e715a">
                            <div>
                                <!--begin::Icon-->
                                <i class="ki-duotone ki-notification-bing fs-2hx" style="color: #2e715a"><span
                                        class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                                <!--end::Icon-->
                            </div>
                            <div>
                                <strong>{{ $withdrawalRequest->coin_amount }}</strong> adet Shoot Coin için
                                <strong>{{ $withdrawalRequest->draw_coin_total_price }}</strong> tutarındaki çekim talebi
                                <strong>{{ $withdrawalRequest->get_approved_at }}</strong> tarhinde
                                onaylanmıştır.
                            </div>
                        </div>
                    @elseif ($withdrawalRequest->status_id === CoinWithdrawalRequest::STATUS_REJECTED)
                        <div class="alert alert-danger d-flex align-items-center gap-2 mb-0" style="color: #555050">
                            <div>
                                <!--begin::Icon-->
                                <i class="ki-duotone ki-notification-bing fs-2hx" style="color: #555050"><span
                                        class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                                <!--end::Icon-->
                            </div>
                            <div>
                                <strong>{{ $withdrawalRequest->coin_amount }}</strong> adet Shoot Coin için
                                <strong>{{ $withdrawalRequest->draw_coin_total_price }}</strong> tutarındaki çekim talebi
                                <strong>{{ $withdrawalRequest->get_rejected_at }}</strong> tarhinde
                                reddedilmiştir.
                            </div>
                        </div>
                    @endif
                    <!--begin::Details-->
                    <div class="mb-20 row g-5">
                        <div class="col-xl-6">
                            <div>
                                <div class="fw-bold mt-5">Kullanıcı ID</div>
                                <div class="text-gray-600">{{ $withdrawalRequest->user->id }}</div>
                            </div>
                            <div>
                                <div class="fw-bold mt-5">E-Posta</div>
                                <div class="text-gray-600">
                                    {{ $withdrawalRequest->user->email }}
                                </div>
                            </div>
                            <div>
                                <div class="fw-bold mt-5">Telefon Numarası</div>
                                <div class="text-gray-600">
                                    {{ $withdrawalRequest->user->phone }}
                                </div>
                            </div>
                            <div>
                                <div class="fw-bold mt-5">Canlı Yayın Kazancı Coin Bakiyesi</div>
                                <div class="text-gray-600 fw-bold d-flex align-items-center gap-2">
                                    <img src="{{ assetAdmin('images/shoot-coin.svg') }}" alt="Shoot Coin"
                                        class="img img-fluid" width="24" height="24">
                                    <div>
                                        {{ $withdrawalRequest->user->earned_coin_balance }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div>
                                <div class="fw-bold mt-5">Para Birimi</div>
                                <div class="text-gray-600">{{ $withdrawalRequest->currency->name }} -
                                    {{ $withdrawalRequest->currency->code }}</div>
                            </div>
                            <div>
                                <div class="fw-bold mt-5">Coin Birim Fiyatı</div>
                                <div class="text-gray-600">{{ $withdrawalRequest->draw_coin_unit_price }}</div>
                            </div>
                            <div>
                                <div class="fw-bold mt-5">Çekilmek İstenen Coin Miktarı</div>
                                <div class="text-gray-600 fw-bold d-flex align-items-center gap-2">
                                    <img src="{{ assetAdmin('images/shoot-coin.svg') }}" alt="Shoot Coin"
                                        class="img img-fluid" width="24" height="24">
                                    <div>
                                        {{ $withdrawalRequest->coin_amount }}
                                    </div>
                                </div>
                            </div>
                            <div>
                                <div class="fw-bold mt-5">Toplam Coin Fiyatı</div>
                                <div class="text-gray-600">{{ $withdrawalRequest->draw_coin_total_price }}</div>
                            </div>
                        </div>
                    </div>
                    <!--end::Details-->

                    <!--begin::Row-->
                    <form id="primaryForm" class="row gap-5">
                        <div class="col-12">
                            @if ($withdrawalRequest->status_id === CoinWithdrawalRequest::STATUS_PENDING)
                                <div class="alert alert-secondary text-gray-700 d-flex align-items-center gap-2 mb-0">
                                    <div>
                                        <!--begin::Icon-->
                                        <i class="ki-duotone ki-notification-bing fs-2hx"><span class="path1"></span><span
                                                class="path2"></span><span class="path3"></span></i>
                                        <!--end::Icon-->
                                    </div>
                                    <div>
                                        Çekim talebi onaylandığında, kullanıcının cüzdan bakiyesinden
                                        <strong>{{ $withdrawalRequest->coin_amount }} shoot coin </strong> otomatik olarak
                                        <strong>
                                            düşülecektir</strong> .
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="col-12 d-flex justify-content-between align-items-center">
                            <div class="d-flex gap-5">
                                @if ($withdrawalRequest->status_id === CoinWithdrawalRequest::STATUS_PENDING)
                                    <button type="button" class="btn btn-primary" data-action-button="approve"
                                        data-action-url="{{ route('admin.coin-withdrawal-requests.approve', ['id' => $withdrawalRequest->id]) }}">Çekim
                                        Talebini
                                        Onayla</button>
                                    <button type="button" class="btn btn-danger" data-action-button="reject"
                                        data-action-url="{{ route('admin.coin-withdrawal-requests.reject', ['id' => $withdrawalRequest->id]) }}">Çekim
                                        Talebini
                                        Reddet</button>
                                @endif
                            </div>
                            <div class="d-flex flex-end gap-5">
                                <a target="_blank" href="{{ $userProfileUrl }}" class="btn btn-primary"><i
                                        class="fa fa-eye"></i> Kullanıcı Profilini Görüntüle</a>
                            </div>
                        </div>
                        @if (
                            $withdrawalRequest->status_id === CoinWithdrawalRequest::STATUS_PENDING ||
                                $withdrawalRequest->status_id === CoinWithdrawalRequest::STATUS_REJECTED)
                            <div class="col-12">
                                <textarea class="form-control placeholder-gsray-600 fw-bold fs-4 ps-9 pt-7" rows="6" name="reject_reason"
                                    placeholder="Red Nedeni"
                                    {{ $withdrawalRequest->status_id === CoinWithdrawalRequest::STATUS_REJECTED ? 'disabled' : '' }}>{{ $withdrawalRequest->reject_reason }}</textarea>
                            </div>
                        @endif
                </div>
                <!--end::Row-->
            </div>
        </div>
    </div>
    </div>
    <!--end::Layout-->
@endsection
@section('scripts')
    <script>
        $(document).ready(function() {
            const approveButton = $('[data-action-button="approve"]');
            const rejectButton = $('[data-action-button="reject"]');
            const rejectReason = $('[name="reject_reason"]');

            approveButton.on('click', () => {
                Swal.fire({
                    icon: 'warning',
                    title: 'Çekim talebini onaylamak istediğinize emin misiniz?',
                    showConfirmButton: true,
                    showCancelButton: true,
                    allowOutsideClick: false,
                    buttonsStyling: false,
                    confirmButtonText: 'Onayla',
                    cancelButtonText: 'Vazgeç',
                    customClass: {
                        confirmButton: "btn btn-primary btn-sm",
                        cancelButton: 'btn btn-secondary btn-sm'
                    }
                }).then((r) => {
                    if (r.isConfirmed) {
                        $.ajax({
                            type: 'POST',
                            url: approveButton.attr('data-action-url'),
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            dataType: 'json',
                            beforeSend: function() {
                                //
                            },
                            success: function(res) {
                                swal.success({
                                    message: res.message
                                }).then(() => window.location.href = res
                                    .redirect_url)
                            },
                            error: function(xhr) {
                                swal.error({
                                    message: xhr.responseJSON?.message ?? null
                                })
                            },
                            complete: function() {
                                //
                            }
                        })
                    }
                })
            })

            rejectButton.on('click', () => {
                Swal.fire({
                    icon: 'warning',
                    title: 'Çekim talebini reddetmek istediğinize emin misiniz?',
                    showConfirmButton: true,
                    showCancelButton: true,
                    allowOutsideClick: false,
                    buttonsStyling: false,
                    confirmButtonText: 'Reddet',
                    cancelButtonText: 'Vazgeç',
                    customClass: {
                        confirmButton: "btn btn-danger btn-sm",
                        cancelButton: 'btn btn-secondary btn-sm'
                    }
                }).then((r) => {
                    if (r.isConfirmed) {
                        $.ajax({
                            type: 'POST',
                            url: rejectButton.attr('data-action-url'),
                            data: {
                                _token: '{{ csrf_token() }}',
                                admin_response: $('[name="admin_response"]').val(),
                                reject_reason: rejectReason.val()
                            },
                            dataType: 'json',
                            beforeSend: function() {
                                //
                            },
                            success: function(res) {
                                swal.success({
                                    message: res.message
                                }).then(() => window.location.href = res
                                    .redirect_url)
                            },
                            error: function(xhr) {
                                swal.error({
                                    message: xhr.responseJSON?.message ?? null
                                })
                            },
                            complete: function() {
                                //
                            }
                        })
                    }
                })
            })
        })
    </script>
@endsection
