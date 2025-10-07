@extends('admin.template')
@section('title', 'Kupon Kodları')
@section('breadcrumb')
    <x-admin.breadcrumb data="Kupon Kodları" />
@endsection
@section('master')
    <!--begin::Card-->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <x-admin.form-elements.search-input attr="data-table-action=search" />
            </div>
            <div class="card-toolbar flex-row-fluid justify-content-end gap-5">
                <a href="{{ route('admin.coupons.create') }}" class="btn btn-primary">
                    <i class="fa fa-plus"></i> Ekle
                </a>
            </div>
        </div>
        <div class="card-body">
            <x-admin.data-table tableId="dataTable">
                <x-slot name="header">
                    <th class="mw-200px">#</th>
                    <th>Kod</th>
                    <th>İndirim Tipi</th>
                    <th>İndirim Tutarı</th>
                    <th>Başlangıç Tarihi</th>
                    <th>Bitiş Tarihi</th>
                    <th>Durum</th>
                    <th>Maks Kullanım</th>
                    <th>Kullanım Sayısı</th>
                    <th>Ülke</th>
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
                        orderable: true
                        ,
                        targets: 7
                    },
                    {
                        orderable: true,
                        targets: 8
                    },
                    {
                        orderable: true,
                        targets: 9
                    },
                    {
                        orderable: false,
                        targets: 10
                    }
                ],
                "processing": true,
                "serverSide": true,
                "ajax": {
                    "url": "{{ route('admin.coupons.data-table') }}",
                    "type": "POST",
                    "data": function(d) {
                        d._token = "{{ csrf_token() }}"
                        d.discount_type = $('[data-table-filter="discount_type"]').val()
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
                            url: `{{ route('admin.coupons.destroy') }}/${id}`,
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