@extends('admin.template')
@use('App\Models\Morph\ReportProblem')
@section('title', 'Panel Kullanıcıları')
@section('breadcrumb')
<x-admin.breadcrumb data="Panel Kullanıcıları" />
@endsection
@section('master')
<!--begin::Card-->
<div class="card">
    <div class="card-header">
        <div class="card-title">
            <x-admin.form-elements.search-input attr="data-table-action=search" />
        </div>
        <div class="card-toolbar flex-row-fluid justify-content-end gap-5">
            <x-admin.add-button class="addBtn" />
        </div>
    </div>
    <div class="card-body">
        <x-admin.data-table tableId="dataTable">
            <x-slot name="header">
                <th>Ad</th>
                <th>Soyad</th>
                <th>E-Posta</th>
                <th>Rol</th>
                <th>İşlemler</th>
            </x-slot>
        </x-admin.data-table>
    </div>
</div>
<!--end::Card-->

<!--start::Modals-->
<x-admin.modals.index id='primaryModal' widthClass="modal-lg">
    <form id='primaryForm' action="">
        @csrf
        <x-slot:title></x-slot>

            <div class="row g-5">
                <div class="col-xl-6">
                    <div class="form-label required">Ad</div>
                    <input required class="form-control" name="first_name">
                </div>
                <div class="col-xl-6">
                    <div class="form-label required">Soyad</div>
                    <input required class="form-control" name="last_name">
                </div>
                <div class="col-xl-6">
                    <div class="form-label required">E-Posta</div>
                    <input type="email" required class="form-control" name="email">
                </div>
                <div class="col-xl-6">
                    <div class="form-label required">Parola</div>
                    <input required class="form-control" name="password">
                </div>
                <div class="col-12">
                    <div class="form-label required">Rol</div>
                    <x-admin.form-elements.role-select name="role_ids[]" customAttr="multiple" dropdownParent="#primaryModal"/>
                </div>
            </div>
            <input type="submit" class="d-none">


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
                    orderable: false,
                    targets: 4
                },
            ],
            "processing": true,
            "serverSide": true,
            "ajax": {
                "url": "{{ route('admin.admins.data-table') }}",
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



        const modal = $('#primaryModal'),
            form = $('#primaryForm'),
            storeUrl = "{{ route('admin.admins.store') }}",
            updateUrl = "{{ route('admin.admins.update') }}";

        const resetForm = () => {
            $('#primaryForm')[0].reset();
            form.find('select[name="role_ids[]"]').val([]).trigger('change')
        }

        $(document).on('click', '.addBtn', function() {
            resetForm()
            form.attr('action', `${storeUrl}`)
            modal.find('.modal-title').text('Ekle')
            modal.modal('show')
        })
        $(document).on('click', '.editBtn', function() {
            resetForm()
            modal.find('.modal-title').text('Düzenle')
            let id = $(this).data('id');
            $.ajax({
                type: 'GET',
                url: `{{ route('admin.admins.get-admin') }}/${id}`,
                dataType: 'json',
                success: function(res) {
                    form.attr('action', `${updateUrl}/${id}`)

                    form.find('input[name="first_name"]').val(res.data.first_name)
                    form.find('input[name="last_name"]').val(res.data.last_name)
                    form.find('input[name="email"]').val(res.data.email)
                    form.find('input[name="password"]').val('')
                    form.find('select[name="role_ids[]"]').val(res.data.roles.map(role => role.id)).trigger('change')

                    modal.modal('show')
                },
                error: function(xhr) {
                    swal.error({
                        message: xhr.responseJSON?.message ?? null
                    })
                }
            })
        })
        $(document).on('click', '#primarySubmitBtn', function() {
            form.submit()
        })
        $(document).on("submit", "#primaryForm", function(e) {
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
                    propSubmitButton($('#primarySubmitBtn'), 1);
                },
                success: function(res) {
                    swal.success({
                        message: res.message
                    })

                    dataTable.draw();
                    modal.modal('hide')
                },
                error: function(xhr) {
                    swal.error({
                        message: xhr.responseJSON?.message ?? null
                    })
                },
                complete: function() {
                    propSubmitButton($('#primarySubmitBtn'), 0);
                }
            })
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
                            url: `{{ route('admin.admins.delete') }}/${id}`,
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