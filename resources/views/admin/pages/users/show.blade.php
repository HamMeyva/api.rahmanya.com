@extends('admin.template')

@use('App\Helpers\CommonHelper')
@use('App\Models\Relations\Team')

@section('title', $user->full_name)
@section('breadcrumb')
    <x-admin.breadcrumb :data="[$user->full_name, 'Kullanıcılar' => route('admin.users.index')]" :backUrl="route('admin.users.index')" />
@endsection
@section('styles')
    <style>
        .blur-background {
            filter: blur(3px);
            transition: filter 0.3s ease;
            pointer-events: none;
            user-select: none;
        }
    </style>
@endsection
@section('master')
    <!--begin::Layout-->
    @if ($user->is_banned)
        <div class="alert alert-danger">
            Kullanıcı <strong>{{ $user->banned_at->format('d-m-Y H:i:s') }}</strong> tarihinde {!! $user->ban_reason ? "<strong>{$user->ban_reason}</strong> sebebiyle" : '' !!}
            engellenmiştir.
        </div>
    @endif
    <div class="d-flex flex-column flex-xl-row">
        <!--begin::Sidebar-->
        <div class="flex-column flex-lg-row-auto w-100 w-xl-350px mb-10">
            <!--begin::Card-->
            <div class="card mb-5 mb-xl-8">
                <!--begin::Card body-->
                <div class="card-body pt-8">
                    <!--begin::Buttons-->
                    <div class="d-flex flex-end flex-wrap gap-5 mb-10">
                        <button class="btn btn-danger btn-sm banBtn" data-type="{{ $user->is_banned ? 'unban' : 'ban' }}"
                            data-title="{{ $user->is_banned ? 'Engeli kaldırmak istediğinize emin misiniz?' : 'Engellemek istediğinize emin misiniz?' }}">
                            <i class="fa fa-ban" style="margin-bottom: 1px"></i>
                            {{ $user->is_banned ? 'Engeli Kaldır' : 'Engelle' }}
                        </button>
                    </div>
                    <!--end::Buttons-->
                    <!--begin::Summary-->
                    <div class="d-flex flex-center flex-column mb-5">
                        <!--begin::Avatar-->
                        <div class="symbol symbol-100px symbol-circle mb-7">
                            @if ($user->avatar)
                                <img src="{{ $user->avatar }}" alt="Avatar" />
                            @else
                                <div class="symbol-label bg-light-primary">
                                    <span
                                        class="text-primary fs-2hx">{{ (new CommonHelper())->getFirstCharacter($user->name) }}</span>
                                </div>
                            @endif
                        </div>
                        <!--end::Avatar-->

                        <!--begin::Name-->
                        <div class="fs-3 text-gray-800 fw-bold mb-1">{{ $user->full_name }}</div>
                        <!--end::Name-->

                        <!--begin::Position-->
                        <div class="fs-5 fw-semibold text-muted mb-6">
                            {{ $user->email }}
                        </div>
                        <!--end::Position-->
                    </div>
                    <!--end::Summary-->

                    <!--begin::Info-->
                    <div class="d-flex flex-wrap flex-center mb-3">
                        <!--begin::Stats-->
                        <div class="border border-gray-300 border-dashed rounded py-3 px-3 mb-3 text-center">
                            <div class="fs-4 fw-bold text-gray-700">
                                <span class="w-75px">{{ $user->user_stats->video_count ?? 0 }}</span>
                            </div>
                            <div class="fw-semibold text-muted">Video</div>
                        </div>
                        <!--end::Stats-->

                        <!--begin::Stats-->
                        <div class="border border-gray-300 border-dashed rounded py-3 px-3 mx-4 mb-3 text-center">
                            <div class="fs-4 fw-bold text-gray-700">
                                <span class="w-50px">{{ $user->user_stats->follower_count ?? 0 }}</span>
                            </div>
                            <div class="fw-semibold text-muted">Takipci</div>
                        </div>
                        <!--end::Stats-->

                        <!--begin::Stats-->
                        <div class="border border-gray-300 border-dashed rounded py-3 px-3 mb-3 text-center">
                            <div class="fs-4 fw-bold text-gray-700">
                                <span class="w-50px">{{ $user->user_stats->following_count ?? 0 }}</< /span>
                            </div>
                            <div class="fw-semibold text-muted">Takip</div>
                        </div>
                        <!--end::Stats-->
                    </div>
                    <!--end::Info-->

                    <!--begin::Details toggle-->
                    <div class="d-flex flex-stack fs-4 py-3">
                        <div class="fw-bold rotate collapsible" data-bs-toggle="collapse" href="#kt_customer_view_details"
                            role="button" aria-expanded="false" aria-controls="kt_customer_view_details">
                            Detaylar
                            <span class="ms-2 rotate-180">
                                <i class="ki-duotone ki-down fs-3"></i>
                            </span>
                        </div>

                        <span
                            class="badge badge-{{ $user->is_approved ? 'success' : 'danger' }}">{{ $user->is_approved ? 'Onaylı Hesap' : 'Onaysız Hesap' }}</span>
                    </div>
                    <!--end::Details toggle-->

                    <div class="separator separator-dashed my-3"></div>

                    <!--begin::Details content-->
                    <div id="kt_customer_view_details" class="collapse show">
                        <div class="py-5 fs-6">
                            <!--begin::Details item-->
                            <div class="fw-bold mt-5">ID</div>
                            <div class="text-gray-600"><span class="badge badge-secondary">{{ $user->id }}</span></div>
                            <!--begin::Details item-->
                            <!--begin::Details item-->
                            <div class="fw-bold mt-5">Kullanıcı Adı</div>
                            <div class="text-gray-600">{{ $user->nickname }}</div>
                            <!--begin::Details item-->
                            <!--begin::Details item-->
                            <div class="fw-bold mt-5">E-Posta</div>
                            <div class="text-gray-600">{{ $user->email }}</div>
                            <!--begin::Details item-->
                            <!--begin::Details item-->
                            <div class="fw-bold mt-5">Ana Takım</div>
                            <div class="text-gray-600">{{ $user->primary_team->name ?? '-' }}</div>
                            <!--begin::Details item-->
                            <!--begin::Details item-->
                            <div class="fw-bold mt-5">Cinsiyet</div>
                            <div class="text-gray-600">{{ $user->gender->name }}</div>
                            <!--begin::Details item-->
                        </div>
                    </div>
                    <!--end::Details content-->
                </div>
                <!--end::Card body-->
            </div>
            <!--end::Card-->
            <!--begin::Notifications-->
            <div class="card mb-5 mb-xl-8">
                <!--begin::Card header-->
                <div class="card-header border-0">
                    <div class="card-title">
                        <h3 class="fw-bold m-0">Bildirimler</h3>
                    </div>
                </div>
                <!--end::Card header-->

                <!--begin::Card body-->
                <div class="card-body pt-2">
                    <!--begin::Items-->
                    <div class="py-2">
                        <!--begin::Item-->
                        <div class="d-flex flex-stack">
                            <div class="d-flex">
                                <div class="d-flex flex-center w-50px">
                                    <i class="fa fa-mail-bulk fs-2x me-3"></i>
                                </div>
                                <div class="d-flex justify-content-center flex-column">
                                    <div class="fs-5 text-gray-900 fw-bold">E-Posta</div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end">
                                <!--begin::Switch-->
                                <label class="form-check form-switch form-switch-sm form-check-custom form-check-solid">
                                    <!--begin::Input-->
                                    <input class="form-check-input notificationInput" name="general_email_notification"
                                        type="checkbox" value="1"
                                        {{ $user->general_email_notification ? 'checked="checked"' : '' }}
                                        id="kt_modal_general_email_notification" />
                                    <!--end::Input-->

                                    <!--begin::Label-->
                                    <span class="form-check-label fw-semibold text-muted"
                                        for="kt_modal_general_email_notification"></span>
                                    <!--end::Label-->
                                </label>
                                <!--end::Switch-->
                            </div>
                        </div>
                        <!--end::Item-->

                        <div class="separator separator-dashed my-5"></div>

                        <!--begin::Item-->
                        <div class="d-flex flex-stack">
                            <div class="d-flex">
                                <div class="d-flex flex-center w-50px">
                                    <i class="fa fa-sms fs-2hx me-4"></i>
                                </div>

                                <div class="d-flex justify-content-center flex-column">
                                    <div class="fs-5 text-gray-900 fw-bold">SMS</div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end">
                                <!--begin::Switch-->
                                <label class="form-check form-switch form-switch-sm form-check-custom form-check-solid">
                                    <!--begin::Input-->
                                    <input class="form-check-input notificationInput" name="general_sms_notification"
                                        type="checkbox" value="1"
                                        {{ $user->general_sms_notification ? 'checked="checked"' : '' }}
                                        id="kt_modal_general_sms_notification" />
                                    <!--end::Input-->

                                    <!--begin::Label-->
                                    <span class="form-check-label fw-semibold text-muted"
                                        for="kt_modal_general_sms_notification"></span>
                                    <!--end::Label-->
                                </label>
                                <!--end::Switch-->
                            </div>
                        </div>
                        <!--end::Item-->

                        <div class="separator separator-dashed my-5"></div>

                        <!--begin::Item-->
                        <div class="d-flex flex-stack">
                            <div class="d-flex">
                                <div class="d-flex flex-center w-50px">
                                    <i class="fa fa-bell fs-2qx me-4"></i>
                                </div>

                                <div class="d-flex justify-content-center flex-column">
                                    <div class="fs-5 text-gray-900 fw-bold">Anlık Bildirimler</div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end">
                                <!--begin::Switch-->
                                <label class="form-check form-switch form-switch-sm form-check-custom form-check-solid">
                                    <!--begin::Input-->
                                    <input class="form-check-input notificationInput" name="general_push_notification"
                                        type="checkbox" value="1"
                                        {{ $user->general_push_notification ? 'checked="checked"' : '' }}
                                        id="kt_modal_general_push_notification" />
                                    <!--end::Input-->

                                    <!--begin::Label-->
                                    <span class="form-check-label fw-semibold text-muted"
                                        for="kt_modal_general_push_notification"></span>
                                    <!--end::Label-->
                                </label>
                                <!--end::Switch-->
                            </div>
                        </div>
                        <!--end::Item-->

                        <div class="separator separator-dashed my-5"></div>

                        <!--begin::Item-->
                        <div class="d-flex flex-stack">
                            <div class="d-flex">
                                <div class="d-flex flex-center w-50px">
                                    <i class="fa fa-bell fs-2qx me-4"></i>
                                </div>

                                <div class="d-flex justify-content-center flex-column">
                                    <div class="fs-5 text-gray-900 fw-bold">Beğeni Bildirimleri</div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end">
                                <!--begin::Switch-->
                                <label class="form-check form-switch form-switch-sm form-check-custom form-check-solid">
                                    <!--begin::Input-->
                                    <input class="form-check-input notificationInput" name="like_notification"
                                        type="checkbox" value="1"
                                        {{ $user->like_notification ? 'checked="checked"' : '' }}
                                        id="kt_modal_like_notification" />
                                    <!--end::Input-->

                                    <!--begin::Label-->
                                    <span class="form-check-label fw-semibold text-muted"
                                        for="kt_modal_like_notification"></span>
                                    <!--end::Label-->
                                </label>
                                <!--end::Switch-->
                            </div>
                        </div>
                        <!--end::Item-->

                        <div class="separator separator-dashed my-5"></div>

                        <!--begin::Item-->
                        <div class="d-flex flex-stack">
                            <div class="d-flex">
                                <div class="d-flex flex-center w-50px">
                                    <i class="fa fa-bell fs-2qx me-4"></i>
                                </div>

                                <div class="d-flex justify-content-center flex-column">
                                    <div class="fs-5 text-gray-900 fw-bold">Yorum Bildirimleri</div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end">
                                <!--begin::Switch-->
                                <label class="form-check form-switch form-switch-sm form-check-custom form-check-solid">
                                    <!--begin::Input-->
                                    <input class="form-check-input notificationInput" name="comment_notification"
                                        type="checkbox" value="1"
                                        {{ $user->comment_notification ? 'checked="checked"' : '' }}
                                        id="kt_modal_comment_notification" />
                                    <!--end::Input-->

                                    <!--begin::Label-->
                                    <span class="form-check-label fw-semibold text-muted"
                                        for="kt_modal_comment_notification"></span>
                                    <!--end::Label-->
                                </label>
                                <!--end::Switch-->
                            </div>
                        </div>
                        <!--end::Item-->

                        <div class="separator separator-dashed my-5"></div>

                        <!--begin::Item-->
                        <div class="d-flex flex-stack">
                            <div class="d-flex">
                                <div class="d-flex flex-center w-50px">
                                    <i class="fa fa-bell fs-2qx me-4"></i>
                                </div>

                                <div class="d-flex justify-content-center flex-column">
                                    <div class="fs-5 text-gray-900 fw-bold">Takipçi Bildirimleri</div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end">
                                <!--begin::Switch-->
                                <label class="form-check form-switch form-switch-sm form-check-custom form-check-solid">
                                    <!--begin::Input-->
                                    <input class="form-check-input notificationInput" name="follower_notification"
                                        type="checkbox" value="1"
                                        {{ $user->follower_notification ? 'checked="checked"' : '' }}
                                        id="kt_modal_follower_notification" />
                                    <!--end::Input-->

                                    <!--begin::Label-->
                                    <span class="form-check-label fw-semibold text-muted"
                                        for="kt_modal_follower_notification"></span>
                                    <!--end::Label-->
                                </label>
                                <!--end::Switch-->
                            </div>
                        </div>
                        <!--end::Item-->

                        <div class="separator separator-dashed my-5"></div>

                        <!--begin::Item-->
                        <div class="d-flex flex-stack">
                            <div class="d-flex">
                                <div class="d-flex flex-center w-50px">
                                    <i class="fa fa-bell fs-2qx me-4"></i>
                                </div>

                                <div class="d-flex justify-content-center flex-column">
                                    <div class="fs-5 text-gray-900 fw-bold">Etiket Bildirimleri</div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end">
                                <!--begin::Switch-->
                                <label class="form-check form-switch form-switch-sm form-check-custom form-check-solid">
                                    <!--begin::Input-->
                                    <input class="form-check-input notificationInput" name="taggable_notification"
                                        type="checkbox" value="1"
                                        {{ $user->taggable_notification ? 'checked="checked"' : '' }}
                                        id="kt_modal_taggable_notification" />
                                    <!--end::Input-->

                                    <!--begin::Label-->
                                    <span class="form-check-label fw-semibold text-muted"
                                        for="kt_modal_taggable_notification"></span>
                                    <!--end::Label-->
                                </label>
                                <!--end::Switch-->
                            </div>
                        </div>
                        <!--end::Item-->
                    </div>
                    <!--end::Items-->
                </div>
                <!--end::Card body-->
            </div>
            <!--end::Notifications-->
        </div>
        <!--end::Sidebar-->

        <!--begin::Content-->
        <div class="flex-lg-row-fluid ms-lg-15">
            <!--begin:::Tabs-->
            <ul class="nav nav-custom nav-tabs nav-line-tabs nav-line-tabs-2x border-0 fs-4 fw-semibold mb-8">
                <!--begin:::Tab item-->
                <li class="nav-item">
                    <a class="nav-link text-active-primary pb-4 active" data-bs-toggle="tab"
                        href="#kt_customer_view_overview_tab">Genel Bakış</a>
                </li>
                <!--end:::Tab item-->
                <!--begin:::Tab item-->
                <li class="nav-item">
                    <a class="nav-link text-active-primary pb-4" data-bs-toggle="tab"
                        href="#kt_general_settings_tab">Genel
                        Ayarlar</a>
                </li>
                <!--end:::Tab item-->
                <!--begin:::Tab item-->
                <li class="nav-item">
                    <a class="nav-link text-active-primary pb-4" data-bs-toggle="tab"
                        href="#kt_sessions_tab">Oturumlar</a>
                </li>
                <!--end:::Tab item-->
                <!--begin:::Tab item-->
                <li class="nav-item">
                    <a class="nav-link text-active-primary pb-4" data-bs-toggle="tab" href="#kt_devices_tab">Cihazlar
                    </a>
                </li>
                <!--end:::Tab item-->
                <!--begin:::Tab item-->
                <li class="nav-item">
                    <a class="nav-link text-active-primary pb-4" data-bs-toggle="tab" href="#kt_device_logins_tab">Cihaz
                        Girişleri
                    </a>
                </li>
                <!--end:::Tab item-->
                <!--begin:::Tab item-->
                <li class="nav-item">
                    <a class="nav-link text-active-primary pb-4" data-bs-toggle="tab"
                        href="#kt_punishments_tab">Cezalar</a>
                </li>
                <!--end:::Tab item-->
            </ul>
            <!--end:::Tabs-->

            <!--begin:::Tab content-->
            <div class="tab-content" id="myTabContent">
                <!--begin:::Tab pane-->
                <div class="tab-pane fade show active" id="kt_customer_view_overview_tab" role="tabpanel">
                    <!--begin::Section-->
                    <div class="row g-5">
                        <div class="col-xl-6">
                            <!--begin::Card-->
                            <div class="card pt-4 h-md-100 mb-6 mb-md-0">
                                <!--begin::Card header-->
                                <div class="card-header border-0">
                                    <!--begin::Card title-->
                                    <div class="card-title">
                                        <h2 class="fw-bold">Shoot Coin</h2>
                                    </div>
                                    <!--end::Card title-->
                                </div>
                                <!--end::Card header-->

                                <!--begin::Card body-->
                                <div class="card-body pt-0">
                                    <div class="fw-bold fs-2">
                                        <div class="d-flex">
                                            <img src="{{ assetAdmin('images/shoot-coin.svg') }}" alt="Shoot Coin"
                                                class="img img-fluid" width="24" height="24">
                                            <div class="ms-2">
                                                {{ $user->coin_balance }}
                                            </div>
                                        </div>
                                        <div class="fs-7 fw-normal text-muted">Shoot coin.</div>
                                    </div>
                                </div>
                                <!--end::Card body-->
                            </div>
                            <!--end::Card-->
                        </div>
                        <div class="col-xl-6">
                            <!--begin::Card-->
                            <div class="card pt-4 h-md-100 mb-6 mb-md-0">
                                <!--begin::Card header-->
                                <div class="card-header border-0">
                                    <!--begin::Card title-->
                                    <div class="card-title">
                                        <h2 class="fw-bold">Canlı Yayın Kazancı</h2>
                                    </div>
                                    <!--end::Card title-->
                                </div>
                                <!--end::Card header-->

                                <!--begin::Card body-->
                                <div class="card-body pt-0">
                                    <div class="fw-bold fs-2">
                                        <div class="d-flex">
                                            <img src="{{ assetAdmin('images/shoot-coin.svg') }}" alt="Shoot Coin"
                                                class="img img-fluid" width="24" height="24">
                                            <div class="ms-2">
                                                {{ $user->earned_coin_balance }}
                                            </div>
                                        </div>
                                        <div class="fs-7 fw-normal text-muted">Shoot coin.</div>
                                    </div>
                                </div>
                                <!--end::Card body-->
                            </div>
                            <!--end::Card-->
                        </div>
                        @if ($user->is_approved)
                            <div class="col-xl-6">
                                <div class="card bg-success h-md-100">
                                    <div class="card-body d-flex align-items-center">
                                        <div>
                                            <div class="text-white fw-bold fs-2">
                                                Onaylı Hesap
                                            </div>
                                            <div class="text-white mt-2"><b>{{ $user->approvedBy->full_name }}</b>
                                                tarafından <b>{{ $user->get_approved_at }}</b> tarihinde doğrulanmıştır.
                                            </div>
                                            <button class="btn btn-light btn-sm mt-3" onclick="rejectUser()">Onayı
                                                Kaldır</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="col-xl-6">
                                <div class="card bg-light-danger h-md-100 border border-danger">
                                    <div class="card-body d-flex align-items-center">
                                        <i class="ki-duotone ki-user-cross text-danger fs-3x me-4"></i>
                                        <div>
                                            <div class="text-danger fw-bold fs-2">Onaysız Hesap</div>
                                            <div class="text-muted mt-2">Bu kullanıcı henüz onaylanmamış.</div>
                                            <button class="btn btn-danger btn-sm mt-3" onclick="approveUser()">Hesabı
                                                Onayla</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                        <div class="col-xl-6">
                            <!--begin::Reward Tier-->
                            <div class="card bg-success h-md-100">
                                <!--begin::Body-->
                                <a href="{{ route('admin.payments.index', ['user_id' => $user->id, 'user_full_name' => $user->full_name]) }}"
                                    class="card-body d-flex flex-column justify-content-center gap-3">
                                    <i class="fa fa-credit-card text-white fs-3x ms-n1"></i>
                                    <div class="text-white fw-bold fs-2">
                                        Ödeme Geçmişini Görüntüle
                                    </div>
                                </a>
                                <!--end::Body-->
                            </div>
                            <!--end::Reward Tier-->
                        </div>
                    </div>
                    <!--end::Section-->
                </div>
                <!--end:::Tab pane-->
                <!--begin:::Tab pane-->
                <div class="tab-pane fade" id="kt_general_settings_tab" role="tabpanel">
                    <!--begin::Card-->
                    <div class="card pt-4 mb-6 mb-xl-9">
                        <!--begin::Card header-->
                        <div class="card-header border-0">
                            <!--begin::Card title-->
                            <div class="card-title">
                                <h2>Profil</h2>
                            </div>
                            <!--end::Card title-->
                        </div>
                        <!--end::Card header-->

                        <!--begin::Card body-->
                        <div class="card-body pt-0 pb-5">
                            <!--begin::Form-->
                            <form class="form row g-2" id="profileForm">
                                @csrf
                                <div class="col-12">
                                    <label class="form-label">Avatar</label>
                                    <!--begin::Image input-->
                                    <div class="mb-3">
                                        <div class="image-input image-input-outline {{ $user->avatar ? '' : 'image-input-empty' }}"
                                            data-kt-image-input="true"
                                            style="background-image: url('{{ assetAdmin('media/svg/avatars/blank.svg') }}')">
                                            <!--begin::Preview existing avatar-->
                                            <div class="image-input-wrapper w-125px h-125px"
                                                style="background-image: url('{{ $user->avatar ?? 'none' }}')"></div>
                                            <!--end::Preview existing avatar-->

                                            <!--begin::Label-->
                                            <label
                                                class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                                data-kt-image-input-action="change">
                                                <i class="ki-duotone ki-pencil fs-7"><span class="path1"></span><span
                                                        class="path2"></span></i>
                                                <!--begin::Inputs-->
                                                <input type="file" name="avatar" accept=".png, .jpg, .jpeg" />
                                                <input type="hidden" name="remove_avatar" />
                                                <!--end::Inputs-->
                                            </label>
                                            <!--end::Label-->

                                            <!--begin::Cancel-->
                                            <span
                                                class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                                data-kt-image-input-action="cancel" data-bs-toggle="tooltip"
                                                title="Vazgeç">
                                                <i class="ki-duotone ki-cross fs-2"><span class="path1"></span><span
                                                        class="path2"></span></i>
                                            </span>
                                            <!--end::Cancel-->

                                            <!--begin::Remove-->
                                            <span
                                                class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                                data-kt-image-input-action="remove" data-bs-toggle="tooltip"
                                                title="Kaldır">
                                                <i class="ki-duotone ki-cross fs-2"><span class="path1"></span><span
                                                        class="path2"></span></i>
                                            </span>
                                            <!--end::Remove-->
                                        </div>
                                    </div>
                                    <!--end::Image input-->
                                </div>
                                <div class="col-xl-6">
                                    <div class="mb-5">
                                        <label class="form-label">Kullanıcı Adı</label>
                                        <input class="form-control" name="nickname" value="{{ $user->nickname }}" />
                                    </div>
                                </div>
                                <div class="col-xl-6">
                                    <div class="mb-5">
                                        <label class="form-label">E-Posta</label>
                                        <input class="form-control" name="email" value="{{ $user->email }}" />
                                    </div>
                                </div>
                                <div class="col-xl-6">
                                    <div class="mb-5">
                                        <label class="form-label">Ad</label>
                                        <input class="form-control" name="name" value="{{ $user->name }}" />
                                    </div>
                                </div>
                                <div class="col-xl-6">
                                    <div class="mb-5">
                                        <label class="form-label">Soyad</label>
                                        <input class="form-control" name="surname" value="{{ $user->surname }}" />
                                    </div>
                                </div>
                                <div class="col-xl-6">
                                    <div class="mb-5">
                                        <label class="form-label">Telefon</label>
                                        <input class="form-control" name="phone" value="{{ $user->phone }}" />
                                    </div>
                                </div>
                                <div class="col-xl-6">
                                    <div class="mb-5">
                                        <label class="form-label">Cinsiyet</label>
                                        <x-admin.form-elements.gender-select name="gender_id" :selectedOption="$user->gender_id"
                                            :hideSearch="true" />
                                    </div>
                                </div>
                                <div class="col-xl-6">
                                    <div class="mb-5">
                                        <label class="form-label">Doğum Tarihi</label>
                                        <x-admin.form-elements.date-input name="birthday" :value="$user->birthday" />
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="mb-5">
                                        <label class="form-label">Hakkında</label>
                                        <textarea class="form-control" name="bio">{!! $user->bio !!}</textarea>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="mb-5">
                                        <label class="form-label">Slogan</label>
                                        <textarea name="slogan" class="form-control">{!! $user->slogan !!}</textarea>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="mb-5">
                                        <label class="form-label">Birinci Takım</label>
                                        <x-admin.form-elements.team-select name="primary_team_id" :selectedOption="$user->primary_team_id" />
                                    </div>
                                </div>
                                <div class="col-12">
                                    <!--begin::Repeater-->
                                    <div id="team_repeater_condition_area">
                                        <!--begin::Form group-->
                                        <div class="form-group">
                                            <div data-repeater-list="team_repeater_condition_area">
                                                <label class="form-label mb-0">İlgilendiği Takımlar</label>
                                                @if (isset($user->teams) && $user->teams->isNotEmpty())
                                                    @foreach ($user->teams as $team)
                                                        <div class="form-group row mt-3" data-repeater-item>
                                                            <div class="col-md-11">
                                                                <select class="form-select" name="team_id"
                                                                    data-kt-repeater="select2"
                                                                    data-placeholder="Takımlar">
                                                                    <option></option>
                                                                    @foreach (Team::all() as $option)
                                                                        <option value="{{ $option->id }}"
                                                                            {{ $team->id == $option->id ? 'selected' : '' }}>
                                                                            {{ $option->name }}
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                            <div class="col-md-1 mt-3 mt-md-1">
                                                                <a href="javascript:;" data-repeater-delete
                                                                    class="btn btn-sm btn-light-danger">
                                                                    <i class="ki-duotone ki-trash fs-5"><span
                                                                            class="path1"></span><span
                                                                            class="path2"></span><span
                                                                            class="path3"></span><span
                                                                            class="path4"></span><span
                                                                            class="path5"></span></i>
                                                                </a>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                @endif
                                                <div class="form-group row mt-3" data-repeater-item>
                                                    <div class="col-md-11">
                                                        <select class="form-select" name="team_id"
                                                            data-kt-repeater="select2" data-placeholder="Takımlar">
                                                            <option></option>
                                                            @foreach (Team::all() as $option)
                                                                <option value="{{ $option->id }}">{{ $option->name }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-md-1 mt-3 mt-md-1">
                                                        <a href="javascript:;" data-repeater-delete
                                                            class="btn btn-sm btn-light-danger">
                                                            <i class="ki-duotone ki-trash fs-5"><span
                                                                    class="path1"></span><span
                                                                    class="path2"></span><span
                                                                    class="path3"></span><span
                                                                    class="path4"></span><span class="path5"></span></i>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <!--end::Form group-->

                                        <!--begin::Form group-->
                                        <div class="form-group mt-5">
                                            <a href="javascript:;" data-repeater-create class="btn btn-light-primary">
                                                <i class="ki-duotone ki-plus fs-3"></i>
                                                Ekle
                                            </a>
                                        </div>
                                        <!--end::Form group-->
                                    </div>
                                    <!--end::Repeater-->
                                </div>
                                <div class="d-flex justify-content-end">
                                    <x-admin.form-elements.submit-btn>Kaydet</x-admin.form-elements.submit-btn>
                                </div>
                            </form>
                            <!--end::Form-->
                        </div>
                        <!--end::Card body-->
                    </div>
                    <!--end::Card-->
                    <!--begin::Card-->
                    <div class="card pt-4 mb-6 mb-xl-9">
                        <!--begin::Card header-->
                        <div class="card-header border-0">
                            <!--begin::Card title-->
                            <div class="card-title">
                                <h2>Güvenlik Ayarları</h2>
                            </div>
                            <!--end::Card title-->
                        </div>
                        <!--end::Card header-->

                        <!--begin::Card body-->
                        <div class="card-body pt-0 pb-5">
                            <!--begin::Table wrapper-->
                            <div class="table-responsive">
                                <!--begin::Table-->
                                <table class="table align-middle table-row-dashed gy-5" id="kt_table_users_login_session">
                                    <!--begin::Table body-->
                                    <tbody class="fs-6 fw-semibold text-gray-600">
                                        <tr>
                                            <td>Parola</td>
                                            <td>******</td>
                                            <td class="text-end">
                                                <button type="button"
                                                    class="btn btn-icon btn-active-light-primary w-30px h-30px ms-auto"
                                                    data-bs-toggle="modal" data-bs-target="#updatePasswordModal">
                                                    <i class="ki-duotone ki-pencil fs-3"><span class="path1"></span><span
                                                            class="path2"></span></i> </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                    <!--end::Table body-->
                                </table>
                                <!--end::Table-->
                            </div>
                            <!--end::Table wrapper-->
                        </div>
                        <!--end::Card body-->
                    </div>
                    <!--end::Card-->
                </div>
                <!--end:::Tab pane-->
                <!--begin:::Tab pane-->
                <div class="tab-pane fade" id="kt_sessions_tab" role="tabpanel">
                    <!--begin::Card-->
                    <div class="card pt-4 mb-6 mb-xl-9">
                        <!--begin::Card header-->
                        <div class="card-header border-0">
                            <!--begin::Card title-->
                            <div class="card-title">
                                <h2>Oturumlar</h2>
                            </div>
                            <!--end::Card title-->

                            <div class="card-toolbar">
                                <div style="width: 220px">
                                    <x-admin.form-elements.date-range-picker name="date_range"
                                        customAttr="sessions-data-table-filter=date_range"
                                        customClass="form-select-sm"
                                        :start="now()->format('d-m-Y')" />
                                </div>
                            </div>
                        </div>
                        <!--end::Card header-->

                        <!--begin::Card body-->
                        <div class="card-body pt-0 pb-5">
                            <x-admin.data-table tableId="sessionsDataTable">
                                <x-slot name="header">
                                    <th>Giriş</th>
                                    <th>Çıkış</th>
                                    <th>Süre</th>
                                </x-slot>
                            </x-admin.data-table>
                        </div>
                        <!--end::Card body-->
                    </div>
                    <!--end::Card-->
                </div>
                <!--end:::Tab pane-->
                <!--begin:::Tab pane-->
                <div class="tab-pane fade" id="kt_devices_tab" role="tabpanel">
                    <!--begin::Card-->
                    <div class="card pt-4 mb-6 mb-xl-9">
                        <!--begin::Card header-->
                        <div class="card-header border-0">
                            <!--begin::Card title-->
                            <div class="card-title">
                                <x-admin.form-elements.search-input attr="devices-data-table-action=search"
                                    class="form-control-sm" />
                            </div>
                            <!--end::Card title-->
                        </div>
                        <!--end::Card header-->

                        <!--begin::Card body-->
                        <div class="card-body pt-0 pb-5">
                            <x-admin.data-table tableId="devicesDataTable">
                                <x-slot name="header">
                                    <th class="mw-30px">Cihaz Id</th>
                                    <th>Marka</th>
                                    <th>Model</th>
                                    <th>İşlemler</th>
                                </x-slot>
                            </x-admin.data-table>
                        </div>
                        <!--end::Card body-->
                    </div>
                    <!--end::Card-->
                </div>
                <!--end:::Tab pane-->
                <!--begin:::Tab pane-->
                <div class="tab-pane fade" id="kt_device_logins_tab" role="tabpanel">
                    <!--begin::Card-->
                    <div class="card pt-4 mb-6 mb-xl-9">
                        <!--begin::Card header-->
                        <div class="card-header border-0">
                            <!--begin::Card title-->
                            <div class="card-title">
                                <x-admin.form-elements.search-input attr="device-logins-data-table-action=search"
                                    class="form-control-sm" />
                            </div>
                            <!--end::Card title-->
                            <!--begin::Card toolbar-->
                            <div class="card-toolbar flex-row-fluid justify-content-end gap-5">
                                <div class="w-100 mw-200px">
                                    <x-admin.form-elements.user-device-select placeholder="Tüm Cihazlar" :userId="$user->id"
                                        :allowClear="true" customAttr="device-logins-data-table-filter=device_unique_id"
                                        customClass="form-select-sm" />
                                </div>
                            </div>
                            <!--end::Card toolbar-->
                        </div>
                        <!--end::Card header-->

                        <!--begin::Card body-->
                        <div class="card-body pt-0 pb-5">
                            <x-admin.data-table tableId="deviceLoginsDataTable">
                                <x-slot name="header">
                                    <th class="mw-30px">Cihaz Id</th>
                                    <th>Giriş Tarihi</th>
                                    <th>Marka</th>
                                    <th>Model</th>
                                    <th>Ip</th>
                                </x-slot>
                            </x-admin.data-table>
                        </div>
                        <!--end::Card body-->
                    </div>
                    <!--end::Card-->
                </div>
                <!--end:::Tab pane-->
                <!--begin:::Tab pane-->
                <div class="tab-pane fade" id="kt_punishments_tab" role="tabpanel">
                    <div class="row g-5">
                        @php
                            $activePunishment = $user->getActivePunishment();
                            if ($activePunishment) {
                                $activePunishment->loadMissing('punishment.category.parent');
                            }
                        @endphp
                        @if ($activePunishment)
                            <div class="col-12">
                                <div class="alert alert-danger">
                                    <b>{{ $activePunishment->punishment->category->parent->name }} -
                                        {{ $activePunishment->punishment->category->name }}</b> Kategorisinde
                                    <b>{{ $activePunishment->created_at->translatedFormat((new CommonHelper())->defaultDateTimeFormat(true)) }}</b>
                                    tarihinde ceza alındı.
                                    <br>
                                    Ceza Sona Erme Tarihi:
                                    <b>{{ $activePunishment->expires_at->translatedFormat((new CommonHelper())->defaultDateTimeFormat(true)) }}</b>
                                </div>
                            </div>
                        @endif
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body py-8">
                                    <!--begin::Accordion-->
                                    <div class="accordion accordion-icon-collapse" id="kt_accordion_3">
                                        <!--begin::Item-->
                                        <div class="">
                                            <!--begin::Header-->
                                            <div class="accordion-header d-flex collapsed" data-bs-toggle="collapse"
                                                data-bs-target="#kt_accordion_3_item_1">
                                                <span class="accordion-icon">
                                                    <i class="ki-duotone ki-plus-square fs-3 accordion-icon-off"><span
                                                            class="path1"></span><span class="path2"></span><span
                                                            class="path3"></span></i>
                                                    <i class="ki-duotone ki-minus-square fs-3 accordion-icon-on"><span
                                                            class="path1"></span><span class="path2"></span></i>
                                                </span>
                                                <h3 class="fs-4 fw-semibold mb-0 ms-4">Ceza Ver</h3>
                                            </div>
                                            <!--end::Header-->

                                            <!--begin::Body-->
                                            <div id="kt_accordion_3_item_1" class="fs-6 collapse mt-8"
                                                data-bs-parent="#kt_accordion_3">
                                                <form id="punishmentForm" class="row g-5">
                                                    @csrf
                                                    <div class="col-12">
                                                        <label class="form-label">Ceza Kategorisi</label>
                                                        <x-admin.form-elements.punishment-category-select
                                                            name="category_id" />
                                                    </div>
                                                    <div class="col-12">
                                                        <label class="form-label">Kartlar</label>
                                                        <ul class="nav nav-pills nav-pills-custom mb-3" role="tablist"
                                                            id="punishmentCardTabs">
                                                            <!--begin::Item-->
                                                            <li class="nav-item mb-3 me-3 me-lg-6" role="presentation">
                                                                <!--begin::Link-->
                                                                <a class="nav-link btn btn-outline btn-flex btn-color-muted btn-active-color-primary flex-column overflow-hidden w-80px h-100px d-flex align-items-center justify-content-center active"
                                                                    id="kt_stats_widget_16_tab_link_1"
                                                                    data-bs-toggle="pill" href="#kt_stats_widget_16_tab_1"
                                                                    aria-selected="true" role="tab"
                                                                    data-card="yellow">
                                                                    <!--begin::Icon-->
                                                                    <div class="nav-icon mb-3">
                                                                        <div
                                                                            style="width: 20px; height: 30px; background-color: yellow">
                                                                        </div>
                                                                    </div>
                                                                    <!--end::Icon-->

                                                                    <!--begin::Title-->
                                                                    <span
                                                                        class="nav-text text-gray-800 fw-bold fs-6 lh-1">Sarı</span>
                                                                    <!--end::Title-->

                                                                    <!--begin::Bullet-->
                                                                    <span
                                                                        class="bullet-custom position-absolute bottom-0 w-100 h-4px bg-primary"></span>
                                                                    <!--end::Bullet-->
                                                                </a>
                                                                <!--end::Link-->
                                                            </li>
                                                            <!--end::Item-->
                                                            <!--begin::Item-->
                                                            <li class="nav-item mb-3 me-3 me-lg-6" role="presentation">
                                                                <!--begin::Link-->
                                                                <a class="nav-link btn btn-outline btn-flex btn-color-muted btn-active-color-primary flex-column overflow-hidden w-80px h-100px d-flex align-items-center justify-content-center"
                                                                    id="kt_stats_widget_16_tab_link_2"
                                                                    data-bs-toggle="pill" href="#kt_stats_widget_16_tab_2"
                                                                    aria-selected="false" role="tab" data-card="red">
                                                                    <!--begin::Icon-->
                                                                    <div class="nav-icon mb-3">
                                                                        <div
                                                                            style="width: 20px; height: 30px; background-color: red">
                                                                        </div>
                                                                    </div>
                                                                    <!--end::Icon-->

                                                                    <!--begin::Title-->
                                                                    <span
                                                                        class="nav-text text-gray-800 fw-bold fs-6 lh-1">Kırmızı</span>
                                                                    <!--end::Title-->

                                                                    <!--begin::Bullet-->
                                                                    <span
                                                                        class="bullet-custom position-absolute bottom-0 w-100 h-4px bg-primary"></span>
                                                                    <!--end::Bullet-->
                                                                </a>
                                                                <!--end::Link-->
                                                            </li>
                                                            <!--end::Item-->
                                                            <!--begin::Item-->
                                                            <li class="nav-item mb-3 me-3 me-lg-6" role="presentation">
                                                                <!--begin::Link-->
                                                                <a class="nav-link btn btn-outline btn-flex btn-color-muted btn-active-color-primary flex-column overflow-hidden w-80px h-100px d-flex align-items-center justify-content-center"
                                                                    id="kt_stats_widget_16_tab_link_3"
                                                                    data-bs-toggle="pill" href="#kt_stats_widget_16_tab_3"
                                                                    aria-selected="false" role="tab"
                                                                    data-card="direct_red">
                                                                    <!--begin::Icon-->
                                                                    <div class="nav-icon mb-3">
                                                                        <div
                                                                            style="width: 20px; height: 30px; background-color: red">
                                                                        </div>
                                                                    </div>
                                                                    <!--end::Icon-->

                                                                    <!--begin::Title-->
                                                                    <span
                                                                        class="nav-text text-gray-800 fw-bold fs-6 lh-1">Doğrudan</span>
                                                                    <!--end::Title-->

                                                                    <!--begin::Bullet-->
                                                                    <span
                                                                        class="bullet-custom position-absolute bottom-0 w-100 h-4px bg-primary"></span>
                                                                    <!--end::Bullet-->
                                                                </a>
                                                                <!--end::Link-->
                                                            </li>
                                                            <!--end::Item-->
                                                        </ul>
                                                    </div>
                                                    <div class="col-12">
                                                        <x-admin.form-elements.submit-btn>Ceza
                                                            Ver</x-admin.form-elements.submit-btn>
                                                    </div>
                                                </form>
                                            </div>
                                            <!--end::Body-->
                                        </div>
                                        <!--end::Item-->
                                    </div>
                                    <!--end::Accordion-->
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <!--begin::Card-->
                            <div class="card pt-4 mb-6 mb-xl-9">
                                <!--begin::Card header-->
                                <div class="card-header border-0">
                                    <!--begin::Card title-->
                                    <div class="card-title">
                                        <h3>Cezalar</h3>
                                    </div>
                                    <!--end::Card title-->
                                    <!--begin::Card toolbar-->
                                    <div class="card-toolbar flex-row-fluid justify-content-end gap-5">
                                        <div class="w-100 mw-200px">

                                        </div>
                                    </div>
                                    <!--end::Card toolbar-->
                                </div>
                                <!--end::Card header-->

                                <!--begin::Card body-->
                                <div class="card-body pt-0 pb-5">
                                    <x-admin.data-table tableId="punishmentsDataTable">
                                        <x-slot name="header">
                                            <th class="mw-30px">#</th>
                                            <th>Kategori</th>
                                            <th>Kart</th>
                                            <th>Alındığı Tarih</th>
                                            <th>Bitiş Tarihi</th>
                                        </x-slot>
                                    </x-admin.data-table>
                                </div>
                                <!--end::Card body-->
                            </div>
                            <!--end::Card-->
                        </div>
                    </div>

                </div>
                <!--end:::Tab pane-->
            </div>
            <!--end:::Tab content-->
        </div>
        <!--end::Content-->
    </div>
    <!--end::Layout-->


    <!--start::Modals-->
    <x-admin.modals.index id='updatePasswordModal'>
        <form id='updatePasswordForm'>
            @csrf
            <x-slot:title>Düzenle</x-slot>

            <div class="row g-5">
                <div class="col-xl-12">
                    <div class="mb-5">
                        <label class="form-label">Parola</label>
                        <input class="form-control" name="password" />
                    </div>
                    <div>
                        <label class="form-label">Parola Tekrar</label>
                        <input class="form-control" name="password_confirmation" />
                    </div>
                </div>
                <input type="submit" class="d-none">
            </div>

            <x-slot:footer>
                <x-admin.form-elements.submit-btn>Kaydet</x-admin.form-elements.submit-btn>
            </x-slot>
        </form>
    </x-admin.modals.index>
    <!--end::Modals-->
@endsection
@section('scripts')
    <script src="{{ assetAdmin('plugins/custom/formrepeater/formrepeater.bundle.js') }}"></script>
    <script>
        const userId = "{{ $user->id }}";
        /* start::Approve User */
        const approveUser = () => {
            Swal.fire({
                icon: 'warning',
                title: 'Onayla',
                html: 'Hesabı onaylamak istediğinize emin misiniz?',
                showConfirmButton: true,
                showCancelButton: true,
                allowOutsideClick: false,
                buttonsStyling: false,
                confirmButtonText: 'Evet',
                cancelButtonText: 'Vazgeç',
                customClass: {
                    confirmButton: "btn btn-primary btn-sm",
                    cancelButton: 'btn btn-secondary btn-sm'
                }
            }).then((r) => {
                if (r.isConfirmed) {
                    $.ajax({
                        type: 'POST',
                        url: `{{ route('admin.users.account.approve', ['id' => $user->id]) }}`,
                        data: {
                            _token: '{{ csrf_token() }}',
                        },
                        dataType: 'json',
                        success: function(res) {
                            swal.success({
                                message: res.message
                            }).then(() => window.location.reload())
                        },
                        error: function(xhr) {
                            swal.error({
                                message: xhr?.responseJSON?.message ?? null
                            })
                        }
                    })
                }
            })
        }
        /* end::Approve User */
        /* start::Reject User */
        const rejectUser = () => {
            Swal.fire({
                icon: 'warning',
                title: 'Reddet',
                html: 'Hesap onayını kaldırmak istediğinize emin misiniz?',
                showConfirmButton: true,
                showCancelButton: true,
                allowOutsideClick: false,
                buttonsStyling: false,
                confirmButtonText: 'Evet',
                cancelButtonText: 'Vazgeç',
                customClass: {
                    confirmButton: "btn btn-danger btn-sm",
                    cancelButton: 'btn btn-secondary btn-sm'
                }
            }).then((r) => {
                if (r.isConfirmed) {
                    $.ajax({
                        type: 'POST',
                        url: `{{ route('admin.users.account.reject', ['id' => $user->id]) }}`,
                        data: {
                            _token: '{{ csrf_token() }}',
                        },
                        dataType: 'json',
                        success: function(res) {
                            swal.success({
                                message: res.message
                            }).then(() => window.location.reload())
                        },
                        error: function(xhr) {
                            swal.error({
                                message: xhr?.responseJSON?.message ?? null
                            })
                        }
                    })
                }
            })
        }
        /* end::Reject User */

        $(document).ready(function() {
            let searchTimeout;
            /* start::Ban */
            $(document).on('click', '.banBtn', function() {
                let title = $(this).data('title'),
                    type = $(this).data('type');
                Swal.fire({
                    icon: 'warning',
                    title: title,
                    input: type == 'ban' ? 'text' : '',
                    inputPlaceholder: 'Gerekçe giriniz',
                    inputAttributes: {
                        'aria-label': 'Gerekçe giriniz'
                    },
                    showConfirmButton: true,
                    showCancelButton: true,
                    allowOutsideClick: false,
                    buttonsStyling: false,
                    confirmButtonText: 'Evet',
                    cancelButtonText: 'Vazgeç',
                    customClass: {
                        confirmButton: "btn btn-danger btn-sm",
                        cancelButton: 'btn btn-secondary btn-sm'
                    }
                }).then((r) => {
                    if (r.isConfirmed) {
                        $.ajax({
                            type: 'POST',
                            url: `{{ route('admin.users.ban', ['id' => $user->id]) }}`,
                            data: {
                                _token: '{{ csrf_token() }}',
                                type: type,
                                reason: r.value
                            },
                            dataType: 'json',
                            success: function(res) {
                                swal.success({
                                    message: res.message
                                }).then(() => window.location.reload())
                            },
                            error: function(xhr) {
                                swal.error({
                                    message: xhr?.responseJSON?.message ?? null
                                })
                            }
                        })
                    }
                })
            })
            /* end::Ban */

            /* start::Profile */
            $(document).on('click', '.notificationInput', function() {
                const isChecked = $(this).is(':checked') ? 1 : 0;
                const name = $(this).attr('name');
                let element = $(this);

                console.log(element)
                $.ajax({
                    type: 'POST',
                    url: `{{ route('admin.users.notification-permission-update', ['id' => $user->id]) }}`,
                    data: {
                        _token: '{{ csrf_token() }}',
                        name: name,
                        is_checked: isChecked
                    },
                    dataType: 'json',
                    beforeSend: function() {
                        element.prop('disabled', true);
                    },
                    success: function(res) {
                        swal.success({
                            message: res.message
                        }).then(() => {
                            //
                        })
                    },
                    error: function(xhr) {
                        swal.error({
                            message: xhr.responseJSON?.message ?? null
                        })
                    },
                    complete: function() {
                        element.prop('disabled', false);
                    }
                })
            })
            $(document).on("submit", "#profileForm", function(e) {
                e.preventDefault();

                let formData = new FormData(this)
                submitBtn = $(this).find("[type='submit']");

                if ($(this).find('.image-input').hasClass('image-input-changed')) {
                    formData.append('avatar_changed', 1);
                }
                $.ajax({
                    type: 'POST',
                    url: "{{ route('admin.users.profile-update', ['id' => $user->id]) }}",
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
                        }).then(() => window.location.reload())
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
            $('#team_repeater_condition_area').repeater({
                initEmpty: false,

                show: function() {
                    $(this).slideDown();
                    $(this).find('[data-kt-repeater="select2"]').select2();
                },

                hide: function(deleteElement) {
                    $(this).slideUp(deleteElement);
                },

                ready: function() {
                    $('[data-kt-repeater="select2"]').select2();
                }
            });
            /* end::Profile */

            /* start::Update Password */
            $(document).on("submit", "#updatePasswordForm", function(e) {
                e.preventDefault();

                let formData = new FormData(this)
                submitBtn = $(this).find("[type='submit']");

                $.ajax({
                    type: 'POST',
                    url: "{{ route('admin.users.update-password', ['id' => $user->id]) }}",
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
                        }).then(() => {
                            $('#updatePasswordModal').modal('hide')
                            $('#updatePasswordModal').find('[name="password"]').val('')
                            $('#updatePasswordModal').find(
                                '[name="password_confirmation"]').val('')
                        })
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
            $(document).on('click', '#updatePasswordModal button[type="submit"]', function(e) {
                e.preventDefault();
                $('#updatePasswordForm').submit();
            })
            /* end::Update Password */


            /* start::Sessions */
            let sessionsDataTable = $("#sessionsDataTable").DataTable({
                order: [],
                columnDefs: [{
                        orderable: true,
                        targets: 0
                    },
                    {
                        orderable: true,
                        targets: 1
                    },
                    {
                        orderable: true,
                        targets: 2
                    }
                ],
                "processing": true,
                "serverSide": true,
                "ajax": {
                    "url": "{{ route('admin.users.sessions.data-table', ['userId' => $user->id]) }}",
                    "type": "POST",
                    "data": function(d) {
                        d._token = "{{ csrf_token() }}"

                        let dateRangePicker = $('[data-table-filter="date_range"]').data(
                            'daterangepicker');
                        d.start_date = dateRangePicker?.startDate.format('YYYY-MM-DD');
                        d.end_date = dateRangePicker?.endDate.format('YYYY-MM-DD');
                    },
                },
            }).on("draw", function(i, j, k) {
                $(this).find('tbody tr [data-bs-toggle="tooltip"]').tooltip()
                KTMenu.createInstances();
            });
            /* end::Sessions */

            /* start::Devices */
            let devicesDataTable = $("#devicesDataTable").DataTable({
                order: [],
                columnDefs: [{
                        orderable: false,
                        targets: 0
                    },
                    {
                        orderable: false,
                        targets: 1
                    },
                    {
                        orderable: false,
                        targets: 2
                    },
                    {
                        orderable: false,
                        targets: 3
                    }
                ],
                "processing": true,
                "serverSide": true,
                "ajax": {
                    "url": "{{ route('admin.users.devices.data-table', ['userId' => $user->id]) }}",
                    "type": "POST",
                    "data": function(d) {
                        d._token = "{{ csrf_token() }}"
                    },
                },
            }).on("draw", function(i, j, k) {
                $(this).find('tbody tr [data-bs-toggle="tooltip"]').tooltip()
                KTMenu.createInstances();
            });
            $(document).on("keyup", "[devices-data-table-action='search']", function() {
                clearTimeout(searchTimeout);
                const searchValue = $(this).val();
                searchTimeout = setTimeout(function() {
                    devicesDataTable.search(searchValue).draw();
                }, 500);
            })

            $(document).on('click', '.blockUserDeviceBtn', function() {
                let type = $(this).data('type'),
                    title = type === 'ban' ? 'Engellemek istediğinize emin misiniz?' :
                    'Engeli kaldırmak istediğinize emin misiniz?';

                Swal.fire({
                    icon: 'warning',
                    title: title,
                    showConfirmButton: true,
                    showCancelButton: true,
                    allowOutsideClick: false,
                    buttonsStyling: false,
                    confirmButtonText: 'Evet',
                    cancelButtonText: 'Vazgeç',
                    customClass: {
                        confirmButton: "btn btn-danger btn-sm",
                        cancelButton: 'btn btn-secondary btn-sm'
                    }
                }).then((r) => {
                    if (r.isConfirmed) {
                        let id = $(this).data('id');
                        $.ajax({
                            type: 'POST',
                            url: `{{ route('admin.users.devices.block', ['userId' => $user->id]) }}/${id}`,
                            data: {
                                _token: '{{ csrf_token() }}',
                                type: type
                            },
                            dataType: 'json',
                            success: function(res) {
                                devicesDataTable.draw()
                                swal.success({
                                    message: res.message
                                })
                            },
                            error: function(xhr) {
                                swal.error({
                                    message: xhr?.responseJSON?.message ?? null
                                })
                            }
                        })
                    }
                })
            })
            /* end::Devices */


            /* start::Device Logins */
            let deviceLoginsDataTable = $("#deviceLoginsDataTable").DataTable({
                order: [],
                columnDefs: [{
                        orderable: false,
                        targets: 0
                    },
                    {
                        orderable: false,
                        targets: 1
                    },
                    {
                        orderable: false,
                        targets: 2
                    },
                    {
                        orderable: false,
                        targets: 3
                    },
                    {
                        orderable: false,
                        targets: 4
                    },
                ],
                "processing": true,
                "serverSide": true,
                "ajax": {
                    "url": "{{ route('admin.users.device-logins.data-table', ['userId' => $user->id]) }}",
                    "type": "POST",
                    "data": function(d) {
                        d._token = "{{ csrf_token() }}"
                        d.device_unique_id = $('[device-logins-data-table-filter="device_unique_id"]')
                            .val()
                    },
                },
            }).on("draw", function() {
                KTMenu.createInstances();
            });
            $(document).on("keyup", "[device-logins-data-table-action='search']", function() {
                clearTimeout(searchTimeout);
                const searchValue = $(this).val();
                searchTimeout = setTimeout(function() {
                    deviceLoginsDataTable.search(searchValue).draw();
                }, 500);
            })
            $(document).on("change", "[device-logins-data-table-filter]", function() {
                deviceLoginsDataTable.draw()
            })
            /* end::Device Logins */

            /* start::Punishments */
            const punishmentForm = $('#punishmentForm');
            const activePunishments = $('#activePunishments');
            const punishmentCardTabs = $('#punishmentCardTabs');


            $(document).on('change', '#punishmentForm [name="category_id"]', function(e) {
                let categoryId = $(this).val();

                $.ajax({
                    type: 'POST',
                    url: "{{ route('admin.users.punishments.get-punishments-by-category', ['userId' => $user->id]) }}",
                    data: {
                        _token: '{{ csrf_token() }}',
                        category_id: categoryId
                    },
                    dataType: 'json',
                    beforeSend: function() {
                        punishmentForm.addClass('blur-background')
                    },
                    success: function(res) {
                        if (res.has_active_yellow) {

                        } else if (res.has_active_red) {

                        }
                    },
                    error: function(xhr) {
                        swal.error({
                            message: xhr?.responseJSON?.message ?? null
                        })
                    },
                    complete: function() {
                        punishmentForm.removeClass('blur-background')
                    }
                })
            })

            $(document).on("submit", "#punishmentForm", function(e) {
                e.preventDefault();

                let formData = new FormData(this)
                formData.append('card_type', $(this).find('#punishmentCardTabs [data-card].active').data(
                    'card'));
                submitBtn = $(this).find("[type='submit']");

                $.ajax({
                    type: 'POST',
                    url: "{{ route('admin.users.punishments.create', ['userId' => $user->id]) }}",
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
                        }).then(() => window.location.reload())
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

            let punishmentsDataTable = $("#punishmentsDataTable").DataTable({
                order: [],
                columnDefs: [{
                        orderable: false,
                        targets: 0
                    },
                    {
                        orderable: false,
                        targets: 1
                    },
                    {
                        orderable: false,
                        targets: 2
                    },
                    {
                        orderable: false,
                        targets: 3
                    },
                    {
                        orderable: false,
                        targets: 4
                    }
                ],
                "processing": true,
                "serverSide": true,
                "ajax": {
                    "url": "{{ route('admin.users.punishments.data-table', ['userId' => $user->id]) }}",
                    "type": "POST",
                    "data": function(d) {
                        d._token = "{{ csrf_token() }}"
                    },
                },
            }).on("draw", function() {
                KTMenu.createInstances();
            });
            $(document).on("keyup", "[punishments-data-table-action='search']", function() {
                clearTimeout(searchTimeout);
                const searchValue = $(this).val();
                searchTimeout = setTimeout(function() {
                    punishmentsDataTable.search(searchValue).draw();
                }, 500);
            })
            $(document).on("change", "[punishments-data-table-filter]", function() {
                punishmentsDataTable.draw()
            })
            /* end::Punishments */


        })
    </script>
@endsection
