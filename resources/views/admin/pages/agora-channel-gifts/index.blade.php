@extends('admin.template')
@section('title', 'Canlı Yayın Hediyeleri')
@section('breadcrumb')
    <x-admin.breadcrumb data="Canlı Yayın Hediyeleri" />
@endsection
@section('master')
    <!--begin::Card-->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <x-admin.form-elements.search-input attr="data-table-action=search" />
            </div>
            <div class="card-toolbar flex-row-fluid justify-content-end gap-5">
                <div style="width: 235px">
                    <x-admin.form-elements.date-range-picker name="date_range" customAttr="data-table-filter=date_range" />
                </div>
                <div style="width: 200px">
                    <x-admin.form-elements.stream-select placeholder="Tüm Canlı Yayınlar" customAttr="data-table-filter=stream_id"
                        :allowClear="true"
                        :selectedOption="request()->query('stream_id') && request()->query('stream_label') ? [
                            'label' => request()->query('stream_label'),
                            'value' => request()->query('stream_id')
                        ] : null" />
                </div>
                <div style="width: 200px">
                    <x-admin.form-elements.user-select placeholder="Tüm Gönderen Kullanıcılar" customAttr="data-table-filter=user_id"
                        :allowClear="true"
                        :selectedOption="request()->query('user_id') && request()->query('user_full_name') ? [
                            'label' => request()->query('user_full_name'),
                            'value' => request()->query('user_id')
                        ] : null" />
                </div>
            </div>
        </div>
        <div class="card-body">
            <x-admin.data-table tableId="dataTable">
                <x-slot name="header">
                    <th>Hediye</th>
                    <th>Tutar</th>
                    <th>Yayın</th>
                    <th>Yayıncı</th>
                    <th>Gönderen</th>
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
                        orderable: true,
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
                        orderable: true,
                        targets: 5
                    },
                ],
                "processing": true,
                "serverSide": true,
                "ajax": {
                    "url": "{{ route('admin.agora-channel-gifts.data-table') }}",
                    "type": "POST",
                    "data": function(d) {
                        d._token = "{{ csrf_token() }}"

                        let dateRangePicker = $('[data-table-filter="date_range"]').data(
                            'daterangepicker');
                        d.start_date = dateRangePicker?.startDate.format('YYYY-MM-DD');
                        d.end_date = dateRangePicker?.endDate.format('YYYY-MM-DD');

                        d.user_id = $('[data-table-filter="user_id"]').val()
                        d.stream_id = $('[data-table-filter="stream_id"]').val()
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
