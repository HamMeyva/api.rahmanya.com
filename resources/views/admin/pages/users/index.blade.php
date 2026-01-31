@extends('admin.template')
@section('title', 'Kullanıcılar')
@section('breadcrumb')
    <x-admin.breadcrumb data="Kullanıcılar" />
@endsection
@section('master')
    <!--begin::Card-->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <x-admin.form-elements.search-input attr="data-table-action=search" />
            </div>
            <div class="card-toolbar flex-row-fluid justify-content-end gap-5">
                <x-admin.filter-menu dropdownId="usersFilterMenu" buttonText="Filtrele">
                    <div class="row gap-5">
                        <div class="col-xl-12">
                            <label class="form-label fs-7">Takım:</label>
                            <div>
                                <x-admin.form-elements.team-select dropdownParent="#usersFilterMenu"
                                    placeholder="Tüm Takımlar" customClass="form-select-sm"
                                    customAttr="data-table-filter=team_id" :allowClear="true" />
                            </div>
                        </div>
                        <div class="col-xl-12">
                            <label class="form-label fs-7">Cinsiyet:</label>
                            <div>
                                <x-admin.form-elements.gender-select dropdownParent="#usersFilterMenu"
                                    placeholder="Tüm Cinsiyetler" customClass="form-select-sm"
                                    customAttr="data-table-filter=gender_id" :allowClear="true" :hideSearch="true" />
                            </div>
                        </div>
                        <div class="col-xl-12 mt-3">
                            <div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch"
                                        id="frozenAccountsFilterSwitch" data-table-filter="is_frozen">
                                    <label class="form-check-label" for="frozenAccountsFilterSwitch">Donuk Hesaplar</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-12">
                            <div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch"
                                        id="privateAccountsFilterSwitch" data-table-filter="is_private">
                                    <label class="form-check-label" for="privateAccountsFilterSwitch">Gizli Hesaplar</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </x-admin.filter-menu>
                <button class="btn btn-primary d-none" data-bs-toggle="modal" data-bs-target="#addAdminModal">
                    <i class="fa fa-plus"></i> Ekle
                </button>
            </div>
        </div>
        <div class="card-body">
            <x-admin.data-table tableId="dataTable">
                <x-slot name="header">
                    <th class="mw-50px">Avatar</th>
                    <th>Id</th>
                    <th>Ad Soyad</th>
                    <th>E-Posta</th>
                    <th>Ana Takım</th>
                    <th>Cinsiyet</th>
                    <th>Video Sayısı</th>
                    <th>Takipci Sayısı</th>
                    <th>Takip Sayısı</th>
                    <th>Donuk Hesap</th>
                    <th>Gizli Hesap</th>
                    <th>İşlemler</th>
                </x-slot>
            </x-admin.data-table>
        </div>
    </div>
    <!--end::Card-->
@endsection
@section('scripts')
    <script>
        $(document).ready(function () {
            let dataTable = $("#dataTable").DataTable({
                order: [],
                columnDefs: [{
                    orderable: false,
                    targets: 0
                },
                {
                    orderable: false,
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
                {
                    orderable: true,
                    targets: 5
                },
                {
                    orderable: false,
                    targets: 6
                },
                {
                    orderable: false,
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
                },
                ],
                "processing": true,
                "serverSide": true,
                "ajax": {
                    "url": "{{ route('admin.users.data-table') }}",
                    "type": "POST",
                    "data": function (d) {
                        d._token = "{{ csrf_token() }}"
                        d.team_id = $('[data-table-filter="team_id"]').val()
                        d.gender_id = $('[data-table-filter="gender_id"]').val()
                        d.is_frozen = $('[data-table-filter="is_frozen"]').is(':checked') ? true : false;
                        d.is_private = $('[data-table-filter="is_private"]').is(':checked') ? true : false;
                    },
                },
            }).on("draw", function () {
                KTMenu.createInstances();
            });

            let searchTimeout;
            $(document).on("keyup", "[data-table-action='search']", function () {
                clearTimeout(searchTimeout);
                const searchValue = $(this).val();
                searchTimeout = setTimeout(function () {
                    dataTable.search(searchValue).draw();
                }, 500);
            })

            $(document).on("change", "[data-table-filter]", function () {
                dataTable.draw()
            })

            // Delete button handler
            $(document).on('click', '.deleteBtn', function () {
                const itemId = $(this).data('id');
                const actionUrl = "{{ route('admin.users.destroy', ['id' => ':id']) }}".replace(':id', itemId);

                Swal.fire({
                    text: "Bu kullanıcıyı silmek istediğinize emin misiniz?",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#3085d6",
                    cancelButtonColor: "#d33",
                    confirmButtonText: "Evet, Sil!",
                    cancelButtonText: "İptal"
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: actionUrl,
                            type: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function (response) {
                                Swal.fire({
                                    text: response.message,
                                    icon: "success"
                                });
                                dataTable.draw();
                            },
                            error: function (xhr) {
                                Swal.fire({
                                    text: xhr.responseJSON?.message || "Bir hata oluştu.",
                                    icon: "error"
                                });
                            }
                        });
                    }
                });
            });
        })
    </script>
@endsection