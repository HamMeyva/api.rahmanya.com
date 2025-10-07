<div>
    <div class="timeline timeline-border-dashed">
        @foreach ($notifications as $notification)
            <!--begin::Timeline item-->
            <div class="timeline-item">
                <!--begin::Timeline line-->
                <div class="timeline-line"></div>
                <!--end::Timeline line-->

                <!--begin::Timeline icon-->
                <div class="timeline-icon me-4">
                    <i class="ki-duotone ki-abstract-26 fs-2 text-gray-500"><span class="path1"></span><span
                            class="path2"></span></i>
                </div>
                <!--end::Timeline icon-->

                <!--begin::Timeline content-->
                <div class="timeline-content mb-10 mt-n2 d-flex justify-content-between align-items-center fw-boldest">
                    <!--begin::Timeline heading-->
                    <div class="overflow-auto pe-3">
                        <!--begin::Title-->
                        <a target="_blank" href="{{ $notification['url'] ?? 'javascript:void(0)' }}" wire:click="markAsRead('{{ $notification['id'] }}')"
                            class="fs-5 fw-{{ $notification['read_at'] ? 'semibold' : 'bolder' }} text-gray-800 text-hover-primary mb-2">{{ $notification['title'] }}</a>
                        <!--end::Title-->

                        <!--begin::Description-->
                        <div class="mb-1 {{ $notification['read_at'] ? '' : 'fw-bolder' }}">{{ $notification['body'] }}</div>
                        <div class="text-muted me-2 fs-7">
                            {{ \Carbon\Carbon::parse($notification['created_at'])->format('d-m-Y H:i:s') }}
                        </div>
                        <!--end::Description-->
                    </div>
                    <!--end::Timeline heading-->

                    <button wire:click="markAsRead('{{ $notification['id'] }}')"
                        class="btn btn-sm btn-icon btn-light-success {{ $notification['read_at'] ? 'd-none' : '' }}"
                        title="Okundu olarak işaretle">
                        <i class="fa fa-eye"></i>
                    </button>
                </div>
                <!--end::Timeline content-->
            </div>
            <!--end::Timeline item-->
        @endforeach
    </div>

    <div class="text-center">
        <div wire:loading>
            <div class="spinner-border text-primary"></div>
        </div>
    </div>

    @if ($hasMorePages)
        <div wire:loading.remove class="text-center mt-4">
            <button wire:click="loadMore" class="btn btn-sm btn-primary">
                Daha Fazla Yükle
            </button>
        </div>
    @endif
</div>
