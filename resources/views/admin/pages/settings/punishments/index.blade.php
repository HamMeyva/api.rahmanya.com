@extends('admin.template')
@section('title', 'Cezalar')
@section('breadcrumb')
    <x-admin.breadcrumb data="Cezalar" />
@endsection
@section('styles')
    <style>
        .punishment-card {
            position: absolute;
            right: -8px;
            top: 50%;
            transform: translateY(-50%);
            width: 12px;
            height: 22px;
            border-radius: 2px;

            &[data-card="yellow"] {
                background-color: yellow;
            }

            &[data-card="red"] {
                background-color: red;
            }
        }
    </style>
@endsection
@section('master')
    <!--begin::Card-->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <x-admin.form-elements.search-input attr="data-table-action=search" />
            </div>
            <div class="card-toolbar flex-row-fluid justify-content-end gap-5">
                <div class="w-100 mw-175px">
                    <x-admin.form-elements.card-select placeholder="Tüm Kartlar" customAttr="data-table-filter=card_type_id"
                        :allowClear="true" :hideSearch="true" />
                </div>
                <button class="btn btn-primary addPunishmentBtn">
                    <i class="fa fa-plus"></i> Ekle
                </button>
            </div>
        </div>
        <div class="card-body">
            <x-admin.data-table tableId="dataTable">
                <x-slot name="header">
                    <th>Ceza</th>
                    <th class="text-center" style="min-width: 150px;">Doğrudan Ceza</th>
                    <th>İşlemler</th>
                </x-slot>
            </x-admin.data-table>
        </div>
    </div>
    <!--end::Card-->

    <!--start::Modals-->
    <x-admin.modals.index id='primaryModal'>
        <form id='primaryForm'>
            @csrf
            <x-slot:title></x-slot>

            <div class="row g-5">
                <div class="col-xl-12">
                    <div class="form-check form-switch form-check-custom me-10">
                        <input class="form-check-input h-30px w-50px" type="checkbox" value="1" name="is_direct_punishment" id="directPunishmentSwitch" />
                        <label class="form-check-label" for="directPunishmentSwitch">
                            Doğrudan Ceza
                        </label>
                    </div>
                </div>
                <div class="col-xl-12">
                    <label class="form-label required">Kategori</label>
                    <x-admin.form-elements.punishment-category-select dropdownParent="#primaryModal" name="punishment_category_id" />
                </div>
                <div class="col-xl-12">
                    <label class="form-label required">Ceza Açıklaması</label>
                    <textarea class="form-control" name="description" rows="2"></textarea>
                </div>
                <div class="col-xl-12">
                    <label class="form-label required">Kart</label>
                    <x-admin.form-elements.card-select name="card_type_id" :hideSearch="true" />
                </div>
                <input type="submit" class="d-none">
            </div>

            <x-slot:footer>
                <x-admin.form-elements.submit-btn id="primarySubmitBtn">Kaydet</x-admin.form-elements.submit-btn>
            </x-slot>
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
                        orderable: false,
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
                    "url": "{{ route('admin.settings.punishments.data-table') }}",
                    "type": "POST",
                    "data": function(d) {
                        d._token = "{{ csrf_token() }}"
                        d.card_type_id = $('[data-table-filter="card_type_id"]').val()
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

            $(document).on('click', '.addPunishmentBtn', function() {
                primaryForm.find('[name="is_direct_punishment"]').prop('checked', false);
                primaryForm.find('[name="description"]').val('');
                primaryForm.find('[name="card_type_id"]').val('').trigger('change');
                primaryForm.find('[name="punishment_category_id"]').val('').trigger('change');

                primaryModal.find('.modal-title').text('Ekle');
                primarySubmitUrl = `{{ route('admin.settings.punishments.store') }}`;
                primaryModal.modal('show')
            })

            $(document).on('click', '.editPunishmentBtn', function() {
                let id = $(this).data('id');

                $.ajax({
                    type: 'GET',
                    url: `{{ route('admin.settings.punishments.show') }}/${id}`,
                    dataType: 'json',
                    success: function(res) {
                        primarySubmitUrl =
                            `{{ route('admin.settings.punishments.update') }}/${id}`;

                        primaryForm.find('[name="is_direct_punishment"]').prop('checked', res.data.is_direct_punishment);
                        primaryForm.find('[name="description"]').val(res.data.description);
                        primaryForm.find('[name="card_type_id"]').val(res.data.card_type_id)
                            .trigger('change');
                        primaryForm.find('[name="punishment_category_id"]').val(res.data.punishment_category_id).trigger('change');

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

            $(document).on('click', '.punishmentBtn', function() {
                primaryForm.submit()
            })

            $(document).on("submit", "#primaryForm", function(e) {
                e.preventDefault();
                let formData = new FormData(this);

                $.ajax({
                    type: 'POST',
                    url: primarySubmitUrl,
                    data: formData,
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
                            url: `{{ route('admin.settings.punishments.destroy') }}/${id}`,
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
        })
    </script>
@endsection
