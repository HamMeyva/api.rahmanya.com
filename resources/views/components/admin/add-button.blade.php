@props([
    'id' => '',
    'label' => 'Ekle',
    'type' => 'button',
    'class' => '',
    'href' => 'javascript:void(0);',
])
<a href="{{ $href }}" id="{{ $id }}" class="d-flex flex-center btn btn-primary {{ $class }}">
    <i class="ki-duotone ki-plus fs-2"></i>{{ $label }}
</a>
