@extends('admin.template')
@use('App\Models\Morph\ReportProblem')
@section('title', 'Müzik Kategorileri')
@section('breadcrumb')
<x-admin.breadcrumb data="Müzik Kategorileri" />
@endsection
@section('master')
<!--begin::Card-->
<div class="card">
    <div class="card-header">
        <div class="card-title">
            <x-admin.form-elements.search-input attr="data-table-action=search" />
        </div>
        <div class="card-toolbar flex-row-fluid justify-content-end gap-5">
            @can('music category create')
                <x-admin.add-button class="addBtn" />
            @endcan
        </div>
    </div>
    <div class="card-body">
        <x-admin.data-table tableId="dataTable">
            <x-slot name="header">
                <th>#</th>
                <th>Ad</th>
                <th>Slug</th>
                <th>İşlemler</th>
            </x-slot>
        </x-admin.data-table>
    </div>
</div>
<!--end::Card-->

<!--start::Modals-->
<x-admin.modals.index id='primaryModal' widthClass="modal-md">
    <form id='primaryForm' action="">
        @csrf
        <x-slot:title></x-slot>

            <div class="row g-5">
                <div class="col-12">
                    <div class="form-label required">Ad</div>
                    <input required class="form-control" name="name">
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
                    orderable: false,
                    targets: 2
                },
                {
                    orderable: false,
                    targets: 3
                }
            ],
            "processing": true,
            "serverSide": true,
            "ajax": {
                "url": "{{ route('admin.musics.categories.data-table') }}",
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
            storeUrl = "{{ route('admin.musics.categories.store') }}",
            updateUrl = "{{ route('admin.musics.categories.update') }}";

        const resetForm = () => {
            $('#primaryForm')[0].reset();
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
                url: `{{ route('admin.musics.categories.show') }}/${id}`,
                dataType: 'json',
                success: function(res) {
                    form.attr('action', `${updateUrl}/${id}`)

                    form.find('input[name="name"]').val(res.data.name)

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
                            url: `{{ route('admin.musics.categories.delete') }}/${id}`,
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