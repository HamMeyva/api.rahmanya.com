@props([
    'itemId' => null,

    'showView' => false,
    'viewUrl' => 'javascript:void(0);',
    'viewBtnClass' => '',

    'showEdit' => false,
    'editUrl' => 'javascript:void(0);',
    'editBtnClass' => '',

    'showDelete' => false,
    'deleteUrl' => 'javascript:void(0);',
    'deleteBtnClass' => '',

    'customButtons' => []
])
{{-- custom buttons ex. => [['url' => route('ex.index')', 'class' => 'custom-class', 'label' => 'Custom 1', 'data-type' => 'action1'],]--}}

<a href="#" class="btn btn-sm btn-light btn-flex btn-center btn-active-light-primary" data-kt-menu-trigger="click"
   data-kt-menu-placement="bottom-end">İşlemler<i class="ki-duotone ki-down fs-5 ms-1"></i></a>
<div
    class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-125px py-4"
    data-kt-menu="true">
    @if ($showView)
        <div class="menu-item px-3">
            <a href="{{ $viewUrl }}"
               class="menu-link px-3 {{ $viewBtnClass }}"
               @if($itemId) data-id="{{$itemId}}" @endif
               data-type="edit">Görüntüle</a>
        </div>
    @endif

    @if ($showEdit)
        <div class="menu-item px-3">
            <a href="{{ $editUrl }}"
               class="menu-link px-3 {{ $editBtnClass }}"
               @if($itemId) data-id="{{$itemId}}" @endif
               data-type="edit">Düzenle</a>
        </div>
    @endif

    @if ($customButtons)
        @foreach ($customButtons as $button)
            <div class="menu-item px-3">
                <a href="{{ $button['url'] ?? 'javascript:void(0);' }}"
                   class="menu-link px-3 {{ $button['class'] ?? '' }}"
                   @isset($button['attrs']) {{$button['attrs']}} @endisset
                   @if($itemId) data-id="{{$itemId}}" @endif>
                    {{ $button['label'] ?? '' }}
                </a>
            </div>
        @endforeach
    @endif

    @if ($showDelete)
        <div class="menu-item px-3">
            <a href="{{ $deleteUrl }}"
               class="menu-link px-3 deleteBtn {{ $deleteBtnClass }}"
               @if($itemId) data-id="{{$itemId}}" @endif
               data-type="delete">Sil</a>
        </div>
    @endif
</div>
