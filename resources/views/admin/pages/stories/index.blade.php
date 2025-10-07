@extends('admin.template')
@section('title', 'Hikayeler')
@section('breadcrumb')
    <x-admin.breadcrumb data="Hikayeler" />
@endsection
@section('master')
    <!--begin::Card-->
    <div class="card card-flush">
        <div class="card-header">
            <div class="card-title">
                <x-admin.form-elements.search-input attr="data-table-action=search" />
            </div>
            <div class="card-toolbar flex-row-fluid justify-content-end gap-5">
                <div class="w-100 mw-100px">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="deletedVideosFilterSwitch"
                            data-table-filter="is_deleted">
                        <label class="form-check-label" for="deletedVideosFilterSwitch">Silinenler</label>
                    </div>
                </div>
                <div class="w-100 mw-175px">
                    <x-admin.form-elements.user-select placeholder="Tüm Kullanıcılar" customAttr="data-table-filter=user_id"
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
                    <th>Kapak</th>
                    <th>Kullanıcı</th>
                    <th>Görüntülenme</th>
                    <th>Beğeni</th>
                    <th>Gizli</th>
                    <th>Konum</th>
                    <th>Oluşturulma</th>
                    <th>Güncellenme</th>
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
                    "url": "{{ route('admin.stories.data-table') }}",
                    "type": "POST",
                    "data": function(d) {
                        d._token = "{{ csrf_token() }}"
                        d.user_id = $('[data-table-filter="user_id"]').val()
                        d.is_deleted = $('[data-table-filter="is_deleted"]').is(':checked') ? true :
                            false;
                        d.is_sport = $('[data-table-filter="is_sport"]').is(':checked') ? true : false;
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
                            url: `{{ route('admin.videos.destroy') }}/${id}`,
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
