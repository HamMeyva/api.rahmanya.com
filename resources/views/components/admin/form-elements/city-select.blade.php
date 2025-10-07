@props([
    'id' => '',
    'name' => 'city_id',
    'customClass' => 'citySelect',
    'dropdownParent' => '',
    'allowClear' => false,
    'placeholder' => '&nbsp;',
    'hideSearch' => false,
    'options' => [],
    'selectedOption' => '',
    'required' => '',
    'customAttr' => '',
    'countryClass' => 'countrySelect'
])
<x-admin.form-elements.select :id="$id"
                              :name="$name"
                              :customClass="$customClass"
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
            let customClass = "{{$customClass}}";
            let select = $(`.${customClass.split(' ')[0]}`);
            select.select2({
                placeholder: "Şehir Seçimi",
                allowClear: true,
                tags: false,
                language: {
                    searching: function () {
                        return "Aranıyor...";
                    },
                    "noResults": function () {
                        return "Sonuç bulunamadı.";
                    },
                    "errorLoading": function () {
                        return "Ülke seçiniz.";
                    }
                },
                ajax: {
                    url: "{{route('admin.cities.search')}}",
                    type: "GET",
                    dataType: 'json',
                    quietMillis: 50,
                    data: function (term) {
                        return {
                            _token: "{{csrf_token()}}",
                            term: term,
                            country_id: $(".{{$countryClass}}").val()
                        };
                    },
                    processResults: function (data) {
                        var res = data.items.map(function (item) {
                            return {
                                id: item.id,
                                text: item.name
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
