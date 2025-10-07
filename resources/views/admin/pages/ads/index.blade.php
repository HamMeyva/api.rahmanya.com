@extends('admin.template')
@use('App\Models\Morph\ReportProblem')
@section('title', 'Reklamlar')
@section('breadcrumb')
    <x-admin.breadcrumb data="Reklamlar" />
@endsection
@section('master')
    <!--begin::Card-->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <x-admin.form-elements.search-input attr="data-table-action=search" />
            </div>
            <div class="card-toolbar flex-row-fluid justify-content-end gap-5">
                <x-admin.add-button :href="route('admin.ads.create')" />
            </div>
        </div>
        <div class="card-body">
            <x-admin.data-table tableId="dataTable">
                <x-slot name="header">
                    <th>Reklam</th>
                    <th>Reklam Veren</th>
                    <th>Durum</th>
                    <th>Ödeme Durumu</th>
                    <th>Bütçe</th>
                    <th>Gösterim Saati</th>
                    <th>Teklif</th>
                    <th>Oluşturulma Tarihi</th>
                    <th>İşlemler</th>
                </x-slot>
            </x-admin.data-table>
        </div>
    </div>
    <!--end::Card-->

    <!--start::Modals-->
    <x-admin.modals.index id='advertiserModal' widthClass="modal-lg">
        <form id='advertiserForm' action="">
            @csrf
            <x-slot:title>Reklam Veren Ekle</x-slot>

            <div class="form-input-area">

            </div>
            <input type="submit" class="d-none">


            <x-slot:footer>
                <x-admin.form-elements.submit-btn id="advertiserSubmitBtn">Kaydet</x-admin.form-elements.submit-btn>
            </x-slot>
        </form>
    </x-admin.modals.index>
    <!--end::Modals-->
    <div class="d-none">
        <x-admin.forms.create-edit-advertiser :init="true" />
    </div>
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
                    "url": "{{ route('admin.ads.data-table') }}",
                    "type": "POST",
                    "data": function(d) {
                        d._token = "{{ csrf_token() }}"
                        //d.entity_type = $('[data-table-filter="entity_type"]').val()
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
