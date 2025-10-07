@props([
    'id' => '',
    'name' => '',
    'select2Class' => 'userSelect',
    'customClass' => '',
    'isSolid' => false,
    'dropdownParent' => '',
    'allowClear' => false,
    'placeholder' => '&nbsp;',
    'hideSearch' => false,
    'options' => [],
    'selectedOption' => '',
    'required' => '',
    'customAttr' => '',
    'relations' => null
])
@php
    $customClass = "{$select2Class} {$customClass}";
@endphp
<x-admin.form-elements.select :id="$id"
                              :name="$name"
                              :customClass="$customClass"
                              :isSolid="$isSolid"
                              :dropdownParent="$dropdownParent"
                              :placeholder="$placeholder"
                              :selectedOption="$selectedOption"
                              :allowClear="$allowClear"
                              :hideSearch="$hideSearch"
                              :customAttr="$customAttr"
                              :ajaxSelect2="true"
                              :required="$required"/>
@push('scripts')
    <script>
        $(document).ready(function () {
            let customClass = "{{$customClass}}",
                select = $(`.${customClass.split(' ')[0]}`);

            select.select2({
                tags: false,
                language: {
                    searching: function () {
                        return "Aranıyor...";
                    },
                    inputTooShort: function () {
                        return "";
                    },
                    "noResults": function () {
                        return "Kayıt bulunamadı.";
                    }
                },
                placeholder: "",
                ajax: {
                    url: '{{route("admin.users.search")}}',
                    dataType: 'json',
                    type: "GET",
                    quietMillis: 50,
                    data: function (term) {
                        return {
                            term: term,
                            relations: "{{$relations}}"
                        };
                    },
                    processResults: function (data) {
                        var res = data.items.map(function (item) {
                            return {
                                id: item.id,
                                text: item.name,
                                extraParams: item.extraParams
                            };
                        });
                        return {
                            results: res
                        };
                    }
                }
            });
        });
    </script>
@endpush
