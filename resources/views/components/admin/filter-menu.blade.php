@props([
    'dropdownId' => 'filterMenu',
    'buttonText' => null,
    'buttonSm' => false,
    'menuHeaderText' => 'Filtre Seçenekleri',
    'icon' => null,
])
<!--begin::Filter menu-->
<div>
    <!--begin::Menu toggle-->
    <a href="#" class="btn btn-flex btn-secondary fw-bold {{ $buttonSm ? 'btn-sm' : '' }}"
        data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
        @if ($icon)
            {!! $icon !!}
        @elseif ($icon === null)
            <i class="ki-duotone ki-filter fs-6 text-muted me-2">
                <span class="path1"></span>
                <span class="path2"></span>
            </i>
        @endif
        @if ($buttonText)
            <span>{{ $buttonText }}</span>
        @endif
    </a>
    <!--end::Menu toggle-->

    <!--begin::Menu 1-->
    <div class="menu menu-sub menu-sub-dropdown w-250px w-md-300px" data-kt-menu="true" id="{{ $dropdownId }}">
        <!--begin::Header-->
        <div class="px-7 py-5">
            <div class="fs-5 text-gray-900 fw-bold">{{ $menuHeaderText }}</div>
        </div>
        <!--end::Header-->

        <!--begin::Menu separator-->
        <div class="separator border-gray-200"></div>
        <!--end::Menu separator-->

        <!--begin::Filters-->
        <div class="px-7 py-5">
            {{ $slot ?? null }}

            <div class="text-end mt-5">
                <button type="button" class="btn btn-sm btn-light btn-active-light-primary"
                    data-kt-menu-dismiss="true">Kapat</button>
            </div>
        </div>
        <!--end::Filters-->


    </div>
    <!--end::Menu 1-->
</div>
<!--end::Filter menu-->
