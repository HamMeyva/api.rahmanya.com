@props([
    'label' => '',
    'url' => '',
    'customClass' => 'text-dark',
    'targetBlank' => false
])
<a {{$targetBlank ? 'target="_blank"' : ''}} href="{{$url}}" class="text-gray-800 text-hover-primary {{$customClass}}">{!! $label !!}</a>