@props([
'id' => '',
'select2' => true,
'name' => '',
'customClass' => '',
'dropdownParent' => '',
'allowClear' => false,
'placeholder' => '&nbsp;',
'hideSearch' => false,
'options' => [],
'selectedOption' => '',
'required' => '',
'customAttr' => '',
'isSolid' => false,
'ajaxSelect2' => false
])
<select id="{{$id}}" name="{{$name}}" @if($select2) data-control="select2" @endif
        @if($dropdownParent) data-dropdown-parent="{{$dropdownParent}}" @endif
        data-allow-clear="{{$allowClear}}"
        data-placeholder="{!! $placeholder ?: "&nbsp;" !!}"
        {{$hideSearch ? "data-hide-search=true" : ""}}
        class="form-select {{$isSolid ? "form-select-solid" : ""}} {{$customClass ?? ""}}" {{$customAttr}} {{$required}}>
    <option value=""></option>
    @if($ajaxSelect2 && $selectedOption)
        <option value="{{$selectedOption["value"]}}" selected>{{$selectedOption["label"]}}</option>
    @else
        @foreach($options as $option)
            <option
                value="{{$option["value"]}}"
                {{isset($option["extraParams"]) ? "data-extra-params=". base64_encode(json_encode($option["extraParams"])) : ""}}
                {{(is_array($selectedOption) && in_array($option["value"], $selectedOption)) || (!is_array($selectedOption) && $selectedOption == $option["value"]) ? "selected" : ""}}
            >{{$option["label"]}}</option>
        @endforeach
    @endif
</select>
