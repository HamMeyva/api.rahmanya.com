<div class="card card-flush h-xl-100" wire:init="loadData">
    <!--begin::Header-->
    <div class="card-header pt-7">
        <!--begin::Title-->
        <h3 class="card-title align-items-start flex-column">
            <span class="card-label fw-bold text-gray-900">Yeni Kayıt Olan Kullanıcılar</span>
            <span class="text-gray-500 mt-1 fw-semibold fs-6">Son 10 Kayıt</span>
        </h3>
        <!--end::Title-->
    </div>
    <!--end::Header-->
    <!--begin::Body-->
    <div class="card-body {{ $isLoading ? 'loading' : '' }}">
        <!--begin::Scroll-->
        <div class="hover-scroll-overlay-y pe-6 me-n6" style="height: 415px">
            @foreach ($users as $user)
                <!--begin::Item-->
                <div class="border border-dashed border-gray-300 rounded p-4 mb-6">
                    <div class="d-flex align-items-center gap-5">
                        <div class="symbol symbol-50px">
                            @if ($user->avatar)
                                <div class="symbol-label" style="background-image:url('{{ $user->avatar }}')"></div>
                            @else
                                <div class="symbol-label fs-2 fw-semibold bg-primary text-inverse-primary">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}</div>
                            @endif
                        </div>

                        <div class="d-flex justify-content-between align-items-center w-100">
                            <div>
                                <a href="{{ route('admin.users.show', ['id' => $user->id]) }}" target="_blank"
                                    class="text-gray-800 fw-bold text-hover-primary fs-6">{{ $user->name }}
                                    {{ $user->surname }}</a>
                                <span class="text-gray-500 fw-semibold d-block fs-7">{{ $user->nickname }}</span>
                            </div>
                            <div>
                                <span class="badge badge-primary badge-sm">{{ $user->created_at->diffForHumans() }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <!--end::Item-->
            @endforeach
        </div>
        <!--end::Scroll-->
    </div>
    <!--end::Body-->
</div>
