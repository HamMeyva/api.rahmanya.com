@extends('admin.template')
@use('App\Models\Morph\ReportProblem')
@section('title', 'Şikayetler')
@section('breadcrumb')
    <x-admin.breadcrumb data="Şikayetler" />
@endsection
@section('master')
    <!--begin::Card-->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <x-admin.form-elements.search-input attr="data-table-action=search" />
            </div>
            <div class="card-toolbar flex-row-fluid justify-content-end gap-5">
                <div class="w-100 mw-200px">
                    <select class="form-select" data-control="select2" data-hide-search="true" data-allow-clear="true" data-placeholder="Tüm Şikayet Türleri" data-table-filter="entity_type">
                        <option value=""></option>
                        @foreach (ReportProblem::$entityTypes as $key => $value)
                            <option value="{{ $key }}">{{ $value }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-100 mw-200px">
                    <x-admin.form-elements.report-problem-category-select placeholder="Tüm Kategoriler" customAttr="data-table-filter=report_problem_category_id"
                        :allowClear="true" :hideSearch="true"/>
                </div>
                <div class="w-100 mw-200px">
                    <x-admin.form-elements.report-problem-status-select placeholder="Tüm Durumlar" customAttr="data-table-filter=status_id"
                        :allowClear="true" :hideSearch="true"/>
                </div>
          
            </div>
        </div>
        <div class="card-body">
            <x-admin.data-table tableId="dataTable">
                <x-slot name="header">
                    <th class="mw-100px">#</th>
                    <th>Tür</th>
                    <th>Kullanıcı</th>
                    <th>Durum</th>
                    <th>Kategori</th>
                    <th>Şikayet Mesajı</th>
                    <th>İlgilenen Admin</th>
                    <th>Admin Cevabı</th>
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
                    },
                    {
                        orderable: false,
                        targets: 8
                    }
                ],
                "processing": true,
                "serverSide": true,
                "ajax": {
                    "url": "{{ route('admin.report-problems.data-table') }}",
                    "type": "POST",
                    "data": function(d) {
                        d._token = "{{ csrf_token() }}"
                        d.entity_type = $('[data-table-filter="entity_type"]').val()
                        d.report_problem_category_id = $('[data-table-filter="report_problem_category_id"]').val()
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

            $(document).on("change", "[data-table-filter]", function() {
                dataTable.draw()
            })
        })
    </script>
@endsection
