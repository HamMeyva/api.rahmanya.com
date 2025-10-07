@extends('admin.template')
@section('title', 'Yasaklı Kelimeler')
@section('breadcrumb')
    <x-admin.breadcrumb data="Yasaklı Kelimeler" />
@endsection
@section('master')
    <!--begin::Card-->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <x-admin.form-elements.search-input attr="data-table-action=search" />
            </div>
            <div class="card-toolbar flex-row-fluid justify-content-end gap-5">
                <button class="btn btn-primary addBtn">
                    <i class="fa fa-plus"></i> Ekle
                </button>
            </div>
        </div>
        <div class="card-body">
            <x-admin.data-table tableId="dataTable">
                <x-slot name="header">
                    <th>#</th>
                    <th>Kelime</th>
                    <th>İşlemler</th>
                </x-slot>
            </x-admin.data-table>
        </div>
    </div>
    <!--end::Card-->

    <!--start::Modals-->
    <x-admin.modals.index id='primaryModal' widthClass="modal-md">
        <form id='primaryForm'>
            @csrf
            <x-slot:title></x-slot>
            <div class="row g-5">
                <div class="col-xl-12">
                    <label class="form-label required">Kelime</label>
                    <input class="form-control" name="word" />
                </div>
            </div>
            <x-slot:footer>
                <x-admin.form-elements.submit-btn id="primarySubmitBtn">Kaydet</x-admin.form-elements.submit-btn>
            </x-slot:footer>
        </form>
    </x-admin.modals.index>
    <!--end::Modals-->
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
                        orderable: false,
                        targets: 2
                    },
                ],
                "processing": true,
                "serverSide": true,
                "ajax": {
                    "url": "{{ route('admin.settings.banned-words.data-table') }}",
                    "type": "POST",
                    "data": function(d) {
                        d._token = "{{ csrf_token() }}"
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

            const primaryForm = $('#primaryForm'),
                primaryModal = $('#primaryModal'),
                primarySubmitBtn = $('#primarySubmitBtn')

            let primarySubmitUrl;

            $(document).on('click', '.addBtn', function() {
                resetPrimaryForm();

                primaryModal.find('.modal-title').text('Ekle');
                primarySubmitUrl = `{{ route('admin.settings.banned-words.store') }}`;
                primaryModal.modal('show')
            })

            $(document).on('click', '.editBtn', function() {
                let id = $(this).data('id');

                $.ajax({
                    type: 'GET',
                    url: `{{ route('admin.settings.banned-words.show') }}/${id}`,
                    dataType: 'json',
                    success: function(res) {
                        primarySubmitUrl =
                            `{{ route('admin.settings.banned-words.update') }}/${id}`;

                        primaryForm.find('[name="word"]').val(res.data.word);

                        primaryModal.find('.modal-title').text('Düzenle');
                        primaryModal.modal('show')
                    },
                    error: function(xhr) {
                        swal.error({
                            message: xhr.responseJSON?.message ?? null
                        })
                    }
                })
            })


            $(document).on("submit", "#primaryForm", function(e) {
                e.preventDefault();

                $.ajax({
                    type: 'POST',
                    url: primarySubmitUrl,
                    data: new FormData(this),
                    dataType: 'json',
                    contentType: false,
                    processData: false,
                    cache: false,
                    beforeSend: function() {
                        propSubmitButton(primarySubmitBtn, 1);
                    },
                    success: function(res) {
                        swal.success({
                            message: res.message
                        })

                        dataTable.draw();

                        primaryModal.modal('hide')
                    },
                    error: function(xhr) {
                        swal.error({
                            message: xhr.responseJSON?.message ?? null
                        })
                    },
                    complete: function() {
                        propSubmitButton(primarySubmitBtn, 0);
                    }
                })
            })

            $(document).on('click', '#primarySubmitBtn', function() {
                primaryForm.submit()
            })

            $(document).on('click', '.deleteBtn', function() {
                Swal.fire({
                    icon: 'warning',
                    title: 'Silmek istediğinize emin misiniz?',
                    showConfirmButton: true,
                    showCancelButton: true,
                    allowOutsideClick: false,
                    buttonsStyling: false,
                    confirmButtonText: 'Sil',
                    cancelButtonText: 'Vazgeç',
                    customClass: {
                        confirmButton: "btn btn-danger btn-sm",
                        cancelButton: 'btn btn-secondary btn-sm'
                    }
                }).then((r) => {
                    if (r.isConfirmed) {
                        let id = $(this).data('id');
                        $.ajax({
                            type: 'POST',
                            url: `{{ route('admin.settings.banned-words.destroy') }}/${id}`,
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            dataType: 'json',
                            success: function(res) {
                                dataTable.draw()
                                swal.success({
                                    message: res.message
                                })
                            },
                            error: function(xhr) {
                                swal.error({
                                    message: xhr?.responseJSON?.message ?? null
                                })
                            }
                        })
                    }
                })
            })

            const resetPrimaryForm = () => {
                primaryForm.find('[name="word"]').val('');
            }
        })
    </script>
@endsection
