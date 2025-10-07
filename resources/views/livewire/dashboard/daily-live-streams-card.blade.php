<div class="card card-flush h-md-50 mb-5 mb-xl-10" wire:init="loadData">
    <!--begin::Card body-->
    <div class="card-body d-flex flex-column justify-content-between {{ $isLoading ? 'loading' : '' }}">
        <div>
            <!--begin::Info-->
            <div class="d-flex align-items-center">
                <!--begin::Amount-->
                <span
                    class="fs-2hx fw-bold text-gray-900 me-2 lh-1 ls-n2">{{ (new App\Helpers\CommonHelper())->formatNumber($count ?? 0) }}</span>
                <!--end::Amount-->
            </div>
            <!--end::Info-->
            <!--begin::Subtitle-->
            <span class="text-gray-500 pt-1 fw-semibold fs-6">Bugün Açılan Canlı Yayın Sayısı</span>
            <!--end::Subtitle-->
        </div>

        <div class="mt-5">
            <!--begin::Info-->
            <div class="d-flex align-items-center">
                <!--begin::Amount-->
                <span
                    class="fs-2hx fw-bold text-gray-900 me-2 lh-1 ls-n2">{{ (new App\Helpers\CommonHelper())->formatDuration($duration ?? 0) }}</span>
                <!--end::Amount-->
            </div>
            <!--end::Info-->
            <!--begin::Subtitle-->
            <span class="text-gray-500 pt-1 fw-semibold fs-6">Bugün Açılan Canlı Yayın Süresi</span>
            <!--end::Subtitle-->
        </div>

        <div class="mt-5">
            <!--begin::Title-->
            <span class="fs-6 fw-bolder text-gray-800 d-block mb-2">Günün Öncü Kullanıcıları</span>
            <!--end::Title-->
            <!--begin::Users group-->
            <div class="symbol-group symbol-hover flex-nowrap">
                @foreach ($featured_users as $featured_user)
                    <a href="{{ route('admin.users.show', ['id' => $featured_user->id]) }}" target="_blank"
                        class="symbol symbol-35px symbol-circle">
                        <span
                            class="symbol-label bg-warning text-inverse-warning fw-bold">{{ strtoupper(substr($featured_user->name, 0, 1)) }}</span>
                        <!-- <img alt="Pic" src="" /> -->
                    </a>
                @endforeach
                <div class="symbol symbol-35px symbol-circle" style="cursor: auto">
                    <span class="symbol-label bg-light text-gray-400 fs-8 fw-bold">+{{ $user_count }}</span>
                </div>
            </div>
            <!--end::Users group-->
        </div>
    </div>
    <!--end::Card body-->
</div>
