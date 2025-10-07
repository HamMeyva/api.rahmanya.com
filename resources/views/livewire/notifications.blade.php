<div class="app-navbar-item ms-1 ms-md-4">
    <!--begin::Menu- wrapper-->
    <div class="btn btn-icon btn-custom btn-icon-muted btn-active-light btn-active-color-primary w-35px h-35px position-relative"
        data-kt-menu-trigger="{default: 'click', lg: 'hover'}" data-kt-menu-attach="parent"
        data-kt-menu-placement="bottom-end" id="kt_menu_item_wow">
        <i class="fa fa-bell fs-2"></i>
        @if ($unreadCount > 0)
            <span class="bullet bullet-dot bg-success h-6px w-6px position-absolute translate-middle animation-blink"
                style="top: 4px; right: 5px">
            </span>
        @endif
    </div>

    <!--begin::Menu-->
    <div class="menu menu-sub menu-sub-dropdown menu-column w-350px w-lg-375px" data-kt-menu="true"
        id="kt_menu_notifications">
        <!--begin::Heading-->
        <div class="d-flex flex-column bgi-no-repeat rounded-top"
            style="background: #2a9b57; background: linear-gradient(90deg,rgba(42, 155, 87, 1) 0%, rgba(48, 191, 108, 1) 51%, rgba(83, 237, 109, 1) 100%);">
            <!--begin::Title-->
            <h3 class="text-white fw-semibold px-9 my-6">
                Bildirimler <span class="fs-8 opacity-75 ps-3">{{ $unreadCount }} bildirim</span>
            </h3>
            <!--end::Title-->
        </div>
        <!--end::Heading-->

        <!--begin::Tab content-->
        <div class="tab-content">
            <!--begin::Items-->
            <div class="scroll-y mh-325px my-5 px-4">
                @if (count($notifications) > 0)
                    @foreach ($notifications as $item)
                        <!--begin::Item-->
                        <div class="d-flex flex-stack py-4 px-4 bg-hover-light rounded-3"
                            wire:click.prevent="markAsRead('{{ $item['id'] }}')">
                            <!--begin::Section-->
                            <div class="d-flex">
                                <!--begin::Symbol-->
                                <div class="symbol symbol-35px me-4">
                                    <span class="symbol-label">
                                        <i class="fa fa-bell fs-2"></i>
                                        @if (!$item['read_at'])
                                            <span
                                                class="bullet bullet-dot bg-success h-6px w-6px position-absolute translate-middle animation-blink"
                                                style="top: 4px; right: 5px"></span>
                                        @endif
                                    </span>
                                </div>
                                <!--end::Symbol-->

                                <!--begin::Title-->
                                <div class="mb-0 me-2">
                                    <div class="fs-7 text-gray-800 fw-bold title">{{ $item['title'] ?? '-' }}</div>
                                    <div class="text-gray-600 fs-7 body">
                                        {{ $item['body'] ? Str::limit($item['body'], 100, '...') : '-' }}</div>
                                </div>
                                <!--end::Title-->
                            </div>
                            <!--end::Section-->

                            <!--begin::Label-->
                            <span
                                class="badge badge-light fs-8 time ms-2">{{ isset($item['created_at'])
                                    ? \Carbon\Carbon::parse($item['created_at'])->diffForHumans([
                                        'parts' => 1,
                                        'join' => false,
                                        'short' => true,
                                        'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE,
                                    ])
                                    : '-' }}</span>
                            <!--end::Label-->
                        </div>
                        <!--end::Item-->

                        @if (!$loop->last)
                            <div class="separator separator-dashed my-2"></div>
                        @endif
                    @endforeach
                @else
                    <div class="fw-semibold text-gray-500 text-center p-5">Bildirim yok</div>
                @endif
            </div>
            <!--end::Items-->

            <!--begin::View more-->
            <div class="py-1 text-center border-top">
                <a href="{{ route('admin.my-profile', ['tab' => 'notifications']) }}" class="btn btn-color-gray-600 btn-active-color-primary">
                    Tümü
                    <i class="ki-duotone ki-arrow-right fs-5"><span class="path1"></span><span
                            class="path2"></span></i> </a>
            </div>
            <!--end::View more-->
        </div>
        <!--end::Tab content-->
    </div>
    <!--end::Menu--> <!--end::Menu wrapper-->
</div>

@push('scripts')
    <script>
        $(document).ready(function() {

            const authId = "{{ auth()->user()->id }}";
            const notificationSoundPath = "{{ assetAdmin('/violet.mp3') }}";
            const notificationSound = new Audio(notificationSoundPath);
            Echo.private(`App.Models.Admin.${authId}`)
                .notification((e) => {
                    console.log(e);


                    Livewire.dispatch("handleNotification", e);

                    notificationSound.play().catch(err => {
                        console.warn('Ses çalınamadı:', err);
                    });
                })

            //fetchNotifications();
        });
    </script>
@endpush
