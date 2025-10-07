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
    'customAttr' => ''
])
<x-admin.form-elements.select :id="$id"
                              :name="$name"
                              :options="$options"
                              :customClass="$customClass"
                              :isSolid="$isSolid"
                              :dropdownParent="$dropdownParent"
                              :placeholder="$placeholder"
                              :selectedOption="$selectedOption"
                              :allowClear="$allowClear"
                              :hideSearch="$hideSearch"
                              :customAttr="$customAttr"
                              :required="$required"/>
