@props([
    'start' => '01-01-2025',
    'end' => now()->format('d-m-Y'),
    'name' => '',
    'daterangepickerClass' => 'date-range-picker',
    'customClass' => '',
    'allowClear' => false,
    'placeholder' => 'Tarih Aralığı Seçiniz',
    'customAttr' => '',
    'dropdownParent' => '',
])
@php
    $customClass = "{$daterangepickerClass} {$customClass}";
@endphp
<div class="position-relative d-flex align-items-center">
    <!--begin::Icon-->
    <i class="ki-duotone ki-calendar-8 fs-2 position-absolute mx-4">
        <span class="path1"></span>
        <span class="path2"></span>
        <span class="path3"></span>
        <span class="path4"></span>
        <span class="path5"></span>
        <span class="path6"></span>
    </i>
    <!--end::Icon-->
    <!--begin::Datepicker-->
    <input class="form-control ps-12 {{ $customClass }}" placeholder="{{ $placeholder }}" name="{{ $name }}"
        {{ $customAttr }} />
    <!--end::Datepicker-->
</div>

@push('scripts')
    <script>
        $(document).ready(function() {
            let customClass = "{{ $customClass }}";
            let input = $(`.${customClass.split(' ')[0]}`);

            let start = moment("{{ $start }}", "DD-MM-YYYY");
            let end = moment("{{ $end }}", "DD-MM-YYYY");

            function cb(start, end) {
                input.html(start.format("DD MMMM YYYY") + " - " + end.format("DD MMMM YYYY"));
            }

            input.daterangepicker({
                parentEl: "{{ $dropdownParent }}",
                startDate: start,
                endDate: end,
                ranges: {
                    "Tüm Zamanlar": [moment("2025-01-01"), moment()],
                    "Bugün": [moment(), moment()],
                    "Dün": [moment().subtract(1, "days"), moment().subtract(1, "days")],
                    "Son 30 Gün": [moment().subtract(29, "days"), moment()],
                    "Bu Ay": [moment().startOf("month"), moment().endOf("month")],
                    "Geçen Ay": [moment().subtract(1, "month").startOf("month"), moment().subtract(1,
                        "month").endOf("month")]
                },
                locale: {
                    format: 'DD-MM-YYYY',
                    applyLabel: 'Uygula',
                    cancelLabel: 'İptal',
                    customRangeLabel: 'Özel Aralık',
                    monthNames: [
                        'Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran',
                        'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'
                    ],
                    daysOfWeek: [
                        'Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cmt', 'Paz',
                    ],
                    firstDayOfWeek: 1,
                    time_24hr: true
                }
            }, cb);

            cb(start, end);
        });
    </script>
@endpush
