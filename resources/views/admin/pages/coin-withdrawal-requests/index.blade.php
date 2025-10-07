@extends('admin.template')
@section('title', 'Çekim Talepleri')
@section('breadcrumb')
    <x-admin.breadcrumb data="Çekim Talepleri" />
@endsection
@section('master')
    <!--begin::Card-->
    <div class="card">
        <div class="card-header">
            <!--start::Card title-->
            <div class="card-title">
                <x-admin.form-elements.search-input attr="data-table-action=search" />
            </div>
            <!--end::Card title-->
            <!--start::Card toolbar-->
            <div class="card-toolbar flex-row-fluid justify-content-end gap-5">
                <x-admin.filter-menu dropdownId="filterMenu">
                    <div class="row gap-5">
                        <div class="col-xl-12">
                            <label class="form-label fs-7">Tarih:</label>
                            <div>
                                <x-admin.form-elements.date-range-picker dropdownParent="#filterMenu" customClass="form-select-sm"
                                    customAttr="data-table-filter=date_range" />
                            </div>
                        </div>
                        <div class="col-xl-12">
                            <label class="form-label fs-7">Durum:</label>
                            <div>
                                <x-admin.form-elements.withdrawal-request-status-select placeholder="Tüm Durumlar"
                                    customClass="form-select-sm" dropdownParent="#filterMenu"
                                    customAttr="data-table-filter=status_id" :allowClear="true" :hideSearch="true" />
                            </div>
                        </div>
                        <div class="col-xl-12">
                            <label class="form-label fs-7">Kullanıcı:</label>
                            <div>
                                <x-admin.form-elements.user-select placeholder="Tüm Kullanıcılar"
                                    customClass="form-select-sm" dropdownParent="#filterMenu"
                                    customAttr="data-table-filter=user_id" :allowClear="true" />
                            </div>
                        </div>
                    </div>
                </x-admin.filter-menu>
            </div>
            <!--end::Card toolbar-->
        </div>
        <div class="card-body">
            <x-admin.data-table tableId="dataTable">
                <x-slot name="header">
                    <th>#</th>
                    <th>Kullanıcı</th>
                    <th>Coin Miktarı</th>
                    <th>Birim Fiyatı</th>
                    <th>Toplam Fiyatı</th>
                    <th>Durum</th>
                    <th>Oluşturulma Tarihi</th>
                    <th>İşlemler</th>
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
                    {
                        orderable: true,
                        targets: 6
                    },
                    {
                        orderable: false,
                        targets: 7
                    }
                ],
                "processing": true,
                "serverSide": true,
                "ajax": {
                    "url": "{{ route('admin.coin-withdrawal-requests.data-table') }}",
                    "type": "POST",
                    "data": function(d) {
                        d._token = "{{ csrf_token() }}"

                        let dateRangePicker = $('[data-table-filter="date_range"]').data(
                            'daterangepicker');
                        d.start_date = dateRangePicker?.startDate.format('YYYY-MM-DD');
                        d.end_date = dateRangePicker?.endDate.format('YYYY-MM-DD');

                        d.user_id = $('[data-table-filter="user_id"]').val()
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
