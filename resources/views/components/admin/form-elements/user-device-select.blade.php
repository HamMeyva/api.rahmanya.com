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
    'userId' => null,
])

@php
    if ($userId) {
        $userDevices = \App\Models\Relations\UserDevice::where('user_id', $userId)
            ->get()
            ->map(function ($item) {
                return [
                    'value' => $item->device_unique_id,
                    'label' => "{$item->device_unique_id} - {$item->device_model}",
                ];
            });
        $options = $userDevices;
    } else {
        $options = [];
    }
@endphp

<x-admin.form-elements.select :id="$id" :name="$name" :options="$options" :customClass="$customClass" :isSolid="$isSolid"
    :dropdownParent="$dropdownParent" :placeholder="$placeholder" :selectedOption="$selectedOption" :allowClear="$allowClear" :hideSearch="$hideSearch" :customAttr="$customAttr"
    :required="$required" />
