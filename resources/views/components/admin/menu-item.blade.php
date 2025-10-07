@props([
'link' => '',
'icon' => '',
'title' => '',
'permission' => null,
])
@if(!is_array($permission) || auth()->user()->canAny($permission))
<div class="menu-item">
    <!--begin:Menu link-->
    <a class="menu-link {{ request()->url() === $link ? 'active' : '' }}" href="{{ $link }}">
        <span class="menu-icon">
            <i class="{{ $icon }}"></i>
        </span>
        <span class="menu-title">{{ $title }}</span>
    </a>
    <!--end:Menu link-->
</div>
@endif