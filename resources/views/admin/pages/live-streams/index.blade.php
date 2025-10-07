@extends('admin.template')
@section('title', 'Canlı Yayınlar')
@section('breadcrumb')
    <x-admin.breadcrumb data="Canlı Yayınlar" />
@endsection
@section('master')
    <!--begin::Card-->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <x-admin.form-elements.search-input attr="data-table-action=search" />
            </div>
            <div class="card-toolbar flex-row-fluid justify-content-end gap-5">
                <div style="width: 240px;">
                    <x-admin.form-elements.date-range-picker customAttr="data-table-filter=date_range" />
                </div>
                <x-admin.filter-menu dropdownId="liveStreamsFilterMenu" buttonText="Filtrele">
                    <div class="row gap-5">
                        <div class="col-xl-12">
                            <label class="form-label fs-7">Yayıncılar:</label>
                            <div>
                                <x-admin.form-elements.user-select dropdownParent="#liveStreamsFilterMenu"
                                    placeholder="Tüm Yayıncılar" customClass="form-select-sm"
                                    customAttr="data-table-filter=user_id" :allowClear="true" />
                            </div>
                        </div>
                    </div>
                </x-admin.filter-menu>
            </div>
        </div>
        <div class="card-body">
            <x-admin.data-table tableId="dataTable">
                <x-slot name="header">
                    <th>Yayın</th>
                    <th style="min-width: 125px;">Yayın Sahibi</th>
                    <th style="min-width: 80px;">Durum</th>
                    <th style="min-width: 125px;">İzleyici Sayısı</th>
                    <th style="min-width: 125px;">Hediye Sayısı</th>
                    <th style="min-width: 125px;">Toplam Coin</th>
                    <th style="min-width: 125px;">Başlangıç Tarihi</th>
                    <th style="min-width: 125px;">Bitiş Tarihi</th>
                    <th>İşlemler</th>
                </x-slot>
            </x-admin.data-table>
        </div>
    </div>
    <!--end::Card-->

    <!--start::Modals-->
    <x-admin.modals.index id='liveStreamModal'>
        <form id='liveStreamForm'>
            @csrf
            <x-slot:title></x-slot>

            <div class="row g-5">
                <div class="col-xl-12">
                    <label class="form-label">Durum</label>
                    <label class="form-check form-switch form-check-custom">
                        <input class="form-check-input" type="checkbox" value="1" name="is_active">
                        <span class="form-check-label fw-semibold text-muted">Aktif</span>
                    </label>
                </div>
                <div class="col-xl-6">
                    <div class="fs-6 fw-semibold mt-2 mb-3">Görsel</div>
                    <!--begin::Image input-->
                    <div class="text-center">
                        <div class="image-input image-input-outline image-input-empty" data-kt-image-input="true"
                            style="background-image: url('{{ assetAdmin('media/svg/avatars/blank.svg') }}')">
                            <!--begin::Preview existing avatar-->
                            <div class="image-input-wrapper w-125px h-125px"></div>
                            <!--end::Preview existing avatar-->

                            <!--begin::Label-->
                            <label class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                data-kt-image-input-action="change" data-bs-toggle="tooltip" title="Değiştir">
                                <i class="ki-duotone ki-pencil fs-7"><span class="path1"></span><span
                                        class="path2"></span></i>
                                <!--begin::Inputs-->
                                <input type="file" name="logo" accept=".png, .jpg, .jpeg" />
                                <input type="hidden" name="remove_image" />
                                <!--end::Inputs-->
                            </label>
                            <!--end::Label-->

                            <!--begin::Cancel-->
                            <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                data-kt-image-input-action="cancel" data-bs-toggle="tooltip" title="İptal">
                                <i class="ki-duotone ki-cross fs-2"><span class="path1"></span><span
                                        class="path2"></span></i>
                            </span>
                            <!--end::Cancel-->

                            <!--begin::Remove-->
                            <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                data-kt-image-input-action="remove" data-bs-toggle="tooltip" title="Kaldır">
                                <i class="ki-duotone ki-cross fs-2"><span class="path1"></span><span
                                        class="path2"></span></i>
                            </span>
                            <!--end::Remove-->
                        </div>
                    </div>
                    <!--end::Image input-->
                </div>
                <div class="col-xl-6">
                    <div class="mb-5">
                        <label class="form-label">Kategori Adı</label>
                        <input class="form-control" name="name" />
                    </div>
                    <div>
                        <label class="form-label">Sıra</label>
                        <input type="number" class="form-control" name="display_order" min="1" step="1" />
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label">Açıklama</label>
                    <textarea class="form-control" name="description"></textarea>
                </div>
                <input type="submit" class="d-none">
            </div>

            <x-slot:footer>
                <x-admin.form-elements.submit-btn id="liveStreamSubmitBtn">Kaydet</x-admin.form-elements.submit-btn>
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
                    "url": "{{ route('admin.live-streams.data-table') }}",
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
        })
    </script>
@endsection
