@props([
    'id' => '',
    'name' => '',
    'customClass' => '',
    'isSolid' => false,
    'dropdownParent' => '',
    'allowClear' => false,
    'placeholder' => '&nbsp;',
    'hideSearch' => false,
    'selectedOption' => '',
    'required' => '',
    'customAttr' => '',
])

<select id="{{ $id }}" name="{{ $name }}"
    @if ($dropdownParent) data-dropdown-parent="{{ $dropdownParent }}" @endif
    data-allow-clear="{{ $allowClear }}" data-placeholder="{!! $placeholder ?: '&nbsp;' !!}"
    {{ $hideSearch ? 'data-hide-search=true' : '' }} data-control="select2"
    class="form-select {{ $isSolid ? 'form-select-solid' : '' }} {{ $customClass ?? '' }}" {{ $customAttr }}
    {{ $required }}>
    <option></option>
    @foreach ($options as $option)
        <optgroup label="{{ $option->name }}">
            @foreach ($option->children as $category)
                <option value="{{ $category->id }}">{{ $category->name }}</option>
            @endforeach
        </optgroup>
    @endforeach
</select>
