@extends('admin.template')
@section('title', 'Videolar')
@section('breadcrumb')
<x-admin.breadcrumb data="Videolar" />
@endsection
@section('master')
<!--begin::Card-->
<div class="card card-flush">
    <div class="card-header">
        <div class="card-title">
            <x-admin.form-elements.search-input attr="data-table-action=search" />
        </div>
        <div class="card-toolbar flex-row-fluid justify-content-end gap-5">
            <button type="button" class="btn btn-danger btn-sm" id="bulkDeleteBtn" style="display:none;">
                <i class="fa fa-trash"></i> Seçilenleri Sil (<span id="selectedCount">0</span>)
            </button>
            <x-admin.filter-menu dropdownId="videosFilterMenu" buttonText="Filtrele">
                <div class="row gap-5">
                    <div class="col-xl-12">
                        <label class="form-label fs-7">Kullanıcı:</label>
                        <div>
                            <x-admin.form-elements.user-select dropdownParent="#videosFilterMenu"
                                placeholder="Tüm Kullanıcılar"
                                customClass="form-select-sm"
                                customAttr="data-table-filter=user_id"
                                :allowClear="true" />
                        </div>
                    </div>
                    <div class="col-xl-12 mt-3">
                        <div class="d-flex">
                            <!--begin::Options-->
                            <label class="form-check form-check-sm form-check-custom form-check-solid me-5">
                                <input class="form-check-input" type="checkbox" value="1" data-table-filter="order_by_new_users">
                                <span class="form-check-label">
                                    Yeni Kullanıcılara Göre
                                </span>
                            </label>
                            <!--end::Options-->
                        </div>
                    </div>
                    <div class="col-xl-12 mt-3">
                        <div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="sportVideosFilterSwitch"
                                    data-table-filter="is_sport">
                                <label class="form-check-label" for="sportVideosFilterSwitch">Sporcu Videoları</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-12">
                        <div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="deletedVideosFilterSwitch"
                                    data-table-filter="is_deleted">
                                <label class="form-check-label" for="deletedVideosFilterSwitch">Silinenler</label>
                            </div>
                        </div>
                    </div>
                </div>
            </x-admin.filter-menu>
        </div>
    </div>
    <div class="card-body">
        <x-admin.data-table tableId="dataTable">
            <x-slot name="header">
                <th><input type="checkbox" id="selectAll" class="form-check-input"></th>
                <th>Kapak</th>
                <th>Kullanıcı, Koleksiyon, Video</th>
                <th>Açıklama</th>
                <th>Kullanıcı</th>
                <th>Beğeni</th>
                <th>Yorum</th>
                <th>Görüntülenme</th>
                <th>Şikayet</th>
                <th>Gizli</th>
                <th>Yorumlar</th>
                <th class="min-w-90px">Öne Çıkarma</th>
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
        let selectedVideos = [];

        let dataTable = $("#dataTable").DataTable({
            order: [],
            pageLength: 5,
            lengthMenu: [5, 10, 25, 50, 100],
            columnDefs: [{
                    orderable: false,
                    targets: 0
                },
                {
                    orderable: false,
                    targets: 1
                },
                {
                    orderable: false,
                    targets: 2
                },
                {
                    orderable: false,
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
                },
                {
                    orderable: true,
                    targets: 9
                },
                {
                    orderable: true,
                    targets: 10
                },
                {
                    orderable: true,
                    targets: 11
                },
                {
                    orderable: true,
                    targets: 12
                },
                {
                    orderable: true,
                    targets: 13
                },
                {
                    orderable: false,
                    targets: 14
                },
            ],
            "processing": true,
            "serverSide": true,
            "ajax": {
                "url": "{{ route('admin.videos.data-table') }}",
                "type": "POST",
                "data": function(d) {
                    d._token = "{{ csrf_token() }}"
                    d.user_id = $('[data-table-filter="user_id"]').val()
                    d.is_deleted = $('[data-table-filter="is_deleted"]').is(':checked') ? true :
                        false;
                    d.is_sport = $('[data-table-filter="is_sport"]').is(':checked') ? true : false;
                    d.order_by_new_users = $('[data-table-filter="order_by_new_users"]').is(
                        ':checked') ? true : false;
                },
            },
        }).on("draw", function() {
            KTMenu.createInstances();
            updateSelectedCount();
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

        // Select All checkbox
        $(document).on('change', '#selectAll', function() {
            const isChecked = $(this).is(':checked');
            $('.video-checkbox:visible').prop('checked', isChecked).trigger('change');
        });

        // Individual checkbox
        $(document).on('change', '.video-checkbox', function() {
            const videoId = $(this).data('video-id');
            if ($(this).is(':checked')) {
                if (!selectedVideos.includes(videoId)) {
                    selectedVideos.push(videoId);
                }
            } else {
                selectedVideos = selectedVideos.filter(id => id !== videoId);
                $('#selectAll').prop('checked', false);
            }
            updateSelectedCount();
        });

        // Bulk delete button
        $(document).on('click', '#bulkDeleteBtn', function() {
            if (selectedVideos.length === 0) return;

            Swal.fire({
                icon: 'warning',
                title: `${selectedVideos.length} videoyu silmek istediğinize emin misiniz?`,
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
                    $.ajax({
                        type: 'POST',
                        url: `{{ route('admin.videos.bulk-destroy') }}`,
                        data: {
                            _token: '{{ csrf_token() }}',
                            video_ids: selectedVideos
                        },
                        dataType: 'json',
                        success: function(res) {
                            selectedVideos = [];
                            $('#selectAll').prop('checked', false);
                            dataTable.draw();
                            updateSelectedCount();
                            swal.success({
                                message: res.message
                            });
                        },
                        error: function(xhr) {
                            swal.error({
                                message: xhr?.responseJSON?.message ?? null
                            });
                        }
                    });
                }
            });
        });

        function updateSelectedCount() {
            $('#selectedCount').text(selectedVideos.length);
            if (selectedVideos.length > 0) {
                $('#bulkDeleteBtn').show();
            } else {
                $('#bulkDeleteBtn').hide();
            }
        }
    })
</script>
@endsection