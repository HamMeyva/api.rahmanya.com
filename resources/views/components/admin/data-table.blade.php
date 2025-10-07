@props([
    "tableId" => "",
    "tableClass" => "",
    "bodyClass" => "",
])
<table id="{{$tableId}}" class="table table-row-bordered gy-3 align-middle {{$tableClass}}">
    <thead>
    <tr class="fw-semibold fs-6 text-gray-900">
        {{ $header ?? ""}}
    </tr>
    </thead>
    <tbody class="fw-semibold text-gray-800 {{$bodyClass}}">
        {{ $slot ?? ""}}
    </tbody>
</table>
