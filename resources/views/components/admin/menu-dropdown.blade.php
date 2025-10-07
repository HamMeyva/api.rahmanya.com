@props([
    "title" => "",
    "icon" => "",
    "routes" => [],
    "permission" => null,
])
@if(!is_array($permission) || auth()->user()->canAny($permission))
<!--begin:Menu item-->
<div data-kt-menu-trigger="click"
     class="menu-item menu-accordion {{Route::is($routes) ? "here show" : ""}}">
    <!--begin:Menu link-->
    <span class="menu-link">
        <span class="menu-icon">
            <i class="{{$icon}}"></i>
        </span>
        <span class="menu-title">{{$title}}</span>
        <span class="menu-arrow"></span>
    </span>
    <!--end:Menu link-->
    <!--begin:Menu sub-->
    <div class="menu-sub menu-sub-accordion">
        {{ $slot ?? null }}
    </div>
    <!--end:Menu sub-->
</div>
<!--end:Menu item-->
@endif
