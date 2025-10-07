@extends('admin.template')
@section('title', 'Meydan Okumalar')
@section('breadcrumb')
    <x-admin.breadcrumb data="Meydan Okumalar" />
@endsection
@section('master')
    <!--begin::Card-->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <x-admin.form-elements.search-input attr="data-table-action=search" />
            </div>
            <div class="card-toolbar flex-row-fluid justify-content-end gap-5">
                <div style="width: 240px;">
                    <x-admin.form-elements.date-range-picker customAttr="data-table-filter=date_range" />
                </div>
            </div>
        </div>
        <div class="card-body">
            <x-admin.data-table tableId="dataTable">
                <x-slot name="header">
                    <th>Yayın</th>
                    <th>Tip</th>
                    <th>Durum</th>
                    <th>Round Süresi</th> 
                    <th>Win Coini</th>
                    <th>Toplanan Coin</th>
                    <th>Başlangıç Tarihi</th>
                    <th>Bitiş Tarihi</th>
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
                        orderable: true,
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
                    {
                        orderable: true,
                        targets: 6
                    },
                    {
                        orderable: true,
                        targets: 7
                    }
                ],
                "processing": true,
                "serverSide": true,
                "ajax": {
                    "url": "{{ route('admin.challenges.data-table') }}",
                    "type": "POST",
                    "data": function(d) {
                        d._token = "{{ csrf_token() }}"

                        let dateRangePicker = $('[data-table-filter="date_range"]').data(
                            'daterangepicker');
                        d.start_date = dateRangePicker?.startDate.format('YYYY-MM-DD');
                        d.end_date = dateRangePicker?.endDate.format('YYYY-MM-DD');
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
