@extends('admin.template')
@section('title', 'Ödemeler')
@section('breadcrumb')
    <x-admin.breadcrumb data="Ödemeler" />
@endsection
@section('master')
    <!--begin::Card-->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <x-admin.form-elements.search-input attr="data-table-action=search" />
            </div>
            <div class="card-toolbar flex-row-fluid justify-content-end gap-5">
                <div class="w-100 mw-225px">
                    <x-admin.form-elements.date-range-picker name="date_range" customAttr="data-table-filter=date_range" />
                </div>
                <div class="w-100 mw-175px">
                    <x-admin.form-elements.user-select placeholder="Tüm Kullanıcılar" customAttr="data-table-filter=user_id"
                        :allowClear="true" :selectedOption="request()->query('user_id') && request()->query('user_full_name')
                            ? [
                                'label' => request()->query('user_full_name'),
                                'value' => request()->query('user_id'),
                            ]
                            : null" />
                </div>
                <x-admin.filter-menu dropdownId="paymentsFilterMenu" buttonText="Filtrele">
                    <div class="row gap-5">
                        <div class="col-xl-12">
                            <label class="form-label fs-7">Kullanıcılar:</label>
                            <div>
                                <x-admin.form-elements.payment-statuses-select dropdownParent="#paymentsFilterMenu"
                                    customClass="form-select-sm" placeholder="Tüm Durumlar"
                                    customAttr="data-table-filter=status_id" :allowClear="true" :hideSearch="true" />
                            </div>
                        </div>
                        <div class="col-xl-12">
                            <label class="form-label fs-7">Ödeme Kanalları:</label>
                            <div>
                                <x-admin.form-elements.payment-channel-select dropdownParent="#paymentsFilterMenu"
                                    customClass="form-select-sm" placeholder="Tüm Ödeme Kanalları"
                                    customAttr="data-table-filter=channel_id" :allowClear="true" :hideSearch="true" />
                            </div>
                        </div>
                    </div>
                </x-admin.filter-menu>
            </div>
        </div>
        <div class="card-body">
            <x-admin.data-table tableId="dataTable">
                <x-slot name="header">
                    <th>#</th>
                    <th>Ödeme Yapan</th>
                    <th>Ödeme</th>
                    <th>Tutar</th>
                    <th>Ödeme Kanalı</th>
                    <th>Durum</th>
                    <th>Başarısızlık Nedeni</th>
                    <th>İşlem Tarihi</th>
                </x-slot>
            </x-admin.data-table>
        </div>
    </div>
    <!--end::Card-->
@endsection
@section('scripts')
    <script>
        $(document).ready(function() {
            let dataTable = $("#dataTable").DataTable({
                order: [],
                columnDefs: [{
                        orderable: false,
                        targets: 0
                    },
                    {
                        orderable: false,
                        targets: 1
                    },
                    {
                        orderable: true,
                        targets: 2
                    },
                    {
                        orderable: true,
                        targets: 3
                    },
                    {
                        orderable: true,
                        targets: 4
                    },
                    {
                        orderable: false,
                        targets: 5
                    },
                    {
                        orderable: true,
                        targets: 6
                    },
                    {
                        orderable: true,
                        targets: 7
                    },
                ],
                "processing": true,
                "serverSide": true,
                "ajax": {
                    "url": "{{ route('admin.payments.data-table') }}",
                    "type": "POST",
                    "data": function(d) {
                        d._token = "{{ csrf_token() }}"

                        let dateRangePicker = $('[data-table-filter="date_range"]').data(
                            'daterangepicker');
                        d.start_date = dateRangePicker?.startDate.format('YYYY-MM-DD');
                        d.end_date = dateRangePicker?.endDate.format('YYYY-MM-DD');

                        d.user_id = $('[data-table-filter="user_id"]').val()
                        d.channel_id = $('[data-table-filter="channel_id"]').val()
                        d.status_id = $('[data-table-filter="status_id"]').val()
                    },
                },
            }).on("draw", function() {
                KTMenu.createInstances();
            });

            let searchTimeout;
            $(document).on("keyup", "[data-table-action='search']", function() {
                clearTimeout(searchTimeout);
                const searchValue = $(this).val();
                searchTimeout = setTimeout(function() {
                    dataTable.search(searchValue).draw();
                }, 500);
            })

            let firstLoad = true; //date range picker yuzunden 2 kere data table render olmasın diye eklendi.
            $(document).on("change", "[data-table-filter]", function() {
                if (firstLoad) {
                    firstLoad = false;
                    return;
                }
                dataTable.draw()
            })
        })
    </script>
@endsection
