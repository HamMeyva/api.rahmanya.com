@props([
    'id' => '',
    'select2' => true,
    'name' => 'country_id',
    'customClass' => 'countrySelect',
    'dropdownParent' => '',
    'allowClear' => false,
    'placeholder' => '&nbsp;',
    'hideSearch' => false,
    'selectedOption' => '',
    'required' => '',
    'customAttr' => ''
])
<x-admin.form-elements.select :id="$id"
                              :select2="$select2"
                              :name="$name"
                              :customClass="$customClass"
                              :dropdownParent="$dropdownParent"
                              :placeholder="$placeholder"
                              :options="$options"
                              :selectedOption="$selectedOption"
                              :allowClear="$allowClear"
                              :hideSearch="$hideSearch"
                              :customAttr="$customAttr"
                              :required="$required"/>
