@extends('admin.template')
@use('App\Models\Morph\ReportProblem')
@section('title', 'Reklam Verenler')
@section('breadcrumb')
    <x-admin.breadcrumb data="Reklam Verenler" />
@endsection
@section('master')
    <!--begin::Card-->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <x-admin.form-elements.search-input attr="data-table-action=search" />
            </div>
            <div class="card-toolbar flex-row-fluid justify-content-end gap-5">
                <x-admin.add-button class="addAdvertiserBtn" />
            </div>
        </div>
        <div class="card-body">
            <x-admin.data-table tableId="dataTable">
                <x-slot name="header">
                    <th>Reklam Veren</th>
                    <th>Hesap Türü</th>
                    <th>E-Posta</th>
                    <th>Telefon</th>
                    <th>Durum</th>
                    <th>Adres</th>
                    <th>Katılım Tarihi</th>
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
                        orderable: false,
                        targets: 7
                    },
                ],
                "processing": true,
                "serverSide": true,
                "ajax": {
                    "url": "{{ route('admin.advertisers.data-table') }}",
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


            const advertiserModal = $('#advertiserModal'),
                advertiserForm = $('#advertiserForm'),
                storeAdvertiserUrl = "{{ route('admin.advertisers.store') }}",
                updateAdvertiserUrl = "{{ route('admin.advertisers.update') }}";

            $(document).on('click', '.addAdvertiserBtn', function() {
                $.ajax({
                    type: 'GET',
                    url: `{{ route('admin.advertisers.get-create-advertiser-form') }}`,
                    dataType: 'json',
                    success: function(res) {
                        advertiserModal.find('.form-input-area').html(res.view)
                        advertiserForm.attr('action', storeAdvertiserUrl)
                        advertiserModal.modal('show')
                    },
                    error: function(xhr) {
                        swal.error({
                            message: xhr.responseJSON?.message ?? null
                        })
                    }
                })
            })
            $(document).on('click', '.editAdvertiserBtn', function() {
                let id = $(this).data('id');

                $.ajax({
                    type: 'GET',
                    url: `{{ route('admin.advertisers.get-edit-advertiser-form') }}/${id}`,
                    dataType: 'json',
                    success: function(res) {
                        advertiserForm.attr('action', `${updateAdvertiserUrl}/${id}`)
                        advertiserModal.find('.form-input-area').html(res.view)
                        initSelect2ForModal(advertiserModal)
                        advertiserModal.modal('show')
                    },
                    error: function(xhr) {
                        swal.error({
                            message: xhr.responseJSON?.message ?? null
                        })
                    }
                })
            })

            function initSelect2ForModal(modal) {
                modal.find('select[data-control="select2"]').each(function() {
                    let select = $(this);
                    select.select2({
                        placeholder: select.attr('placeholder'),
                        allowClear: select.data('allow-clear') ? true : false,
                        minimumResultsForSearch: select.data('hide-search') ? -1 : 1,
                        dropdownParent: modal
                    });
                })
            }

            $(document).on('click', '#advertiserSubmitBtn', function() {
                $('#advertiserForm').submit()
            })
            $(document).on("submit", "#advertiserForm", function(e) {
                e.preventDefault();
                let formData = new FormData(this),
                    url = $(this).attr('action');

                $.ajax({
                    type: 'POST',
                    url: url,
                    data: formData,
                    dataType: 'json',
                    contentType: false,
                    processData: false,
                    cache: false,
                    beforeSend: function() {
                        propSubmitButton($('#advertiserSubmitBtn'), 1);
                    },
                    success: function(res) {
                        swal.success({
                            message: res.message
                        })

                        dataTable.draw();
                        advertiserModal.modal('hide')
                    },
                    error: function(xhr) {
                        swal.error({
                            message: xhr.responseJSON?.message ?? null
                        })
                    },
                    complete: function() {
                        propSubmitButton($('#advertiserSubmitBtn'), 0);
                    }
                })
            })
        })
    </script>
@endsection
