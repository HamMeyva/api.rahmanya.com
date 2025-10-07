@props([
    'id' => null,
    'title' => null,
    'footer' => null,
    'widthClass' => 'mw-550px'
])
<div class="modal fade" tabindex="-1" id="{{$id}}">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable {{$widthClass}}">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">{{$title}}</h3>

                <!--begin::Close-->
                <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal"
                     aria-label="Close">
                    <i class="ki-duotone ki-cross fs-2x"><span class="path1"></span><span class="path2"></span></i>
                </div>
                <!--end::Close-->
            </div>

            <div class="modal-body">
                @if ($slot->isNotEmpty())
                    {{ $slot }}
                @endif
            </div>

            <div class="modal-footer d-flex justify-content-center">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                @if ($footer)
                    {{ $footer }}
                @endif
            </div>
        </div>
    </div>
</div>
