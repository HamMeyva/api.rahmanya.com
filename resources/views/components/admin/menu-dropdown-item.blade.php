@props([
    'title' => '',
    'link' => '',
    'permission' => null,
])
@if(!is_array($permission) || auth()->user()->canAny($permission))
<!--begin:Menu item-->
<div class="menu-item">
    <!--begin:Menu link-->
    <a class="menu-link {{request()->fullUrlIs($link) ? "active" : ""}}" href="{{$link}}">
        <span class="menu-bullet">
            <span class="bullet bullet-dot"></span>
        </span>
        <span class="menu-title">{{$title}}</span>
    </a>
    <!--end:Menu link-->
</div>
<!--end:Menu item-->
@endif
