@extends('admin.template')
@section('title', 'Onay Bekleyen Havaleler')
@section('breadcrumb')
    <x-admin.breadcrumb data="Onay Bekleyen Havaleler" />
@endsection
@section('master')
    <!--begin::Card-->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <x-admin.form-elements.search-input attr="data-table-action=search" />
            </div>
            <div class="card-toolbar flex-row-fluid justify-content-end gap-5">
                <div class="w-100 mw-225px">
                    <x-admin.form-elements.date-range-picker name="date_range" customAttr="data-table-filter=date_range" />
                </div>
                <div class="w-100 mw-175px">
                    <x-admin.form-elements.user-select placeholder="Tüm Kullanıcılar" customAttr="data-table-filter=user_id"
                        :allowClear="true" :selectedOption="request()->query('user_id') && request()->query('user_full_name')
                            ? [
                                'label' => request()->query('user_full_name'),
                                'value' => request()->query('user_id'),
                            ]
                            : null" />
                </div>
            </div>
        </div>
        <div class="card-body">
            <x-admin.data-table tableId="dataTable">
                <x-slot name="header">
                    <th>#</th>
                    <th>Tutar</th>
                    <th>Kullanıcı</th>
                    <th>İşlem Tarihi</th>
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
                        orderable: false,
                        targets: 4
                    }
                ],
                "processing": true,
                "serverSide": true,
                "ajax": {
                    "url": "{{ route('admin.payments.waiting-approval.data-table') }}",
                    "type": "POST",
                    "data": function(d) {
                        d._token = "{{ csrf_token() }}"

                        let dateRangePicker = $('[data-table-filter="date_range"]').data(
                            'daterangepicker');
                        d.start_date = dateRangePicker?.startDate.format('YYYY-MM-DD');
                        d.end_date = dateRangePicker?.endDate.format('YYYY-MM-DD');

                        d.user_id = $('[data-table-filter="user_id"]').val()
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

            $(document).on('click', '.approveBtn', function() {
                const id = $(this).data('id'),
                    button = $(this);

                Swal.fire({
                    icon: 'warning',
                    title: 'Ödemeyi onaylamak istediğinize emin misiniz?',
                    showConfirmButton: true,
                    showCancelButton: true,
                    allowOutsideClick: false,
                    buttonsStyling: false,
                    confirmButtonText: 'Onayla',
                    cancelButtonText: 'Vazgeç',
                    customClass: {
                        confirmButton: "btn btn-success btn-sm",
                        cancelButton: 'btn btn-secondary btn-sm'
                    }
                }).then((r) => {
                    if (r.isConfirmed) {
                        $.ajax({
                            type: 'POST',
                            url: `{{ route('admin.payments.approve') }}/${id}`,
                            dataType: 'json',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            beforeSend: function() {
                                propSubmitButton(button, 1);
                            },
                            success: function(res) {
                                dataTable.draw();

                                swal.success({
                                    message: res.message
                                })
                            },
                            error: function(xhr) {
                                swal.error({
                                    message: xhr.responseJSON?.message ?? null
                                })
                            },
                            complete: function() {
                                propSubmitButton(button, 0);
                            }
                        })
                    }
                })
            })

            $(document).on('click', '.rejectBtn', function() {
                const id = $(this).data('id'),
                    button = $(this);

                Swal.fire({
                    icon: 'warning',
                    title: 'Ödemeyi reddetmek istediğinize emin misiniz?',
                    input: 'text',
                    inputPlaceholder: 'Reddetme sebebini yazınız...',
                    showConfirmButton: true,
                    showCancelButton: true,
                    allowOutsideClick: false,
                    buttonsStyling: false,
                    confirmButtonText: 'Reddet',
                    cancelButtonText: 'Vazgeç',
                    customClass: {
                        confirmButton: "btn btn-danger btn-sm",
                        cancelButton: 'btn btn-secondary btn-sm'
                    }
                }).then((r) => {
                    if (r.isConfirmed) {
                        $.ajax({
                            type: 'POST',
                            url: `{{ route('admin.payments.reject') }}/${id}`,
                            dataType: 'json',
                            data: {
                                _token: '{{ csrf_token() }}',
                                failure_reason: r.value
                            },
                            beforeSend: function() {
                                propSubmitButton(button, 1);
                            },
                            success: function(res) {
                                dataTable.draw();

                                swal.success({
                                    message: res.message
                                })
                            },
                            error: function(xhr) {
                                swal.error({
                                    message: xhr.responseJSON?.message ?? null
                                })
                            },
                            complete: function() {
                                propSubmitButton(button, 0);
                            }
                        })
                    }
                })
            })
        })
    </script>
@endsection
