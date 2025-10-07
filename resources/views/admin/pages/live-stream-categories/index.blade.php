@extends('admin.template')
@section('title', 'Canlı Yayın Kategorileri')
@section('breadcrumb')
    <x-admin.breadcrumb data="Canlı Yayın Kategorileri" />
@endsection
@section('master')
    <!--begin::Card-->
    <div class="card">
        <div class="card-header">   
            <div class="card-title">
                <x-admin.form-elements.search-input attr="data-table-action=search" />
            </div>
            <div class="card-toolbar flex-row-fluid justify-content-end gap-5">
                <button class="btn btn-primary addCategoryBtn">
                    <i class="fa fa-plus"></i> Ekle
                </button>
            </div>
        </div>
        <div class="card-body">
            <x-admin.data-table tableId="dataTable">
                <x-slot name="header">
                    <th class="mw-20px">#</th>
                    <th>Adı</th>
                    <th>Sıra</th>
                    <th>Durum</th>
                    <th>İşlemler</th>
                </x-slot>
            </x-admin.data-table>
        </div>
    </div>
    <!--end::Card-->

    <!--start::Modals-->
    <x-admin.modals.index id='categoryModal'>
        <form id='categoryForm'>
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
                                data-kt-image-input-action="change" data-bs-toggle="tooltip"
                                title="Değiştir">
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
                                data-kt-image-input-action="remove" data-bs-toggle="tooltip"
                                title="Kaldır">
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
                <x-admin.form-elements.submit-btn id="categorySubmitBtn">Kaydet</x-admin.form-elements.submit-btn>
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
                    }
                ],
                "processing": true,
                "serverSide": true,
                "ajax": {
                    "url": "{{ route('admin.live-streams.categories.data-table') }}",
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


            const categoryForm = $('#categoryForm'),
                categoryModal = $('#categoryModal'),
                categorySubmitBtn = $('#categorySubmitBtn')

            let categorySubmitUrl;

            $(document).on('click', '.addCategoryBtn', function() {
                categoryForm.find('.image-input').addClass('image-input-empty');
                categoryForm.find('.image-input-wrapper').css('background-image', 'none');
                categoryForm.find('[name="is_active"]').prop('checked', true);
                categoryForm.find('[name="name"]').val('');
                categoryForm.find('[name="description"]').val('');
                categoryForm.find('[name="display_order"]').val('');


                categoryModal.find('.modal-title').text('Ekle');
                categorySubmitUrl = `{{ route('admin.live-streams.categories.store') }}`;
                categoryModal.modal('show')
            })

            $(document).on('click', '.editCategoryBtn', function() {
                let id = $(this).data('id'),
                    imageInput = categoryForm.find('.image-input');

                $.ajax({
                    type: 'GET',
                    url: `{{ route('admin.live-streams.categories.show') }}/${id}`,
                    dataType: 'json',
                    success: function(res) {
                        categorySubmitUrl = `{{ route('admin.live-streams.categories.update') }}/${id}`;

                        if (res.data.icon) {
                            imageInput.removeClass('image-input-empty');
                            categoryForm.find('.image-input-wrapper').css('background-image',
                                'url(' + res.data.get_icon_url + ')');
                        } else {
                            imageInput.addClass('image-input-empty');
                            categoryForm.find('.image-input-wrapper').css('background-image',
                                'none');
                        }
                        
                        categoryForm.find('[name="is_active"]').prop('checked', res.data.is_active);
                        categoryForm.find('[name="name"]').val(res.data.name);
                        categoryForm.find('[name="description"]').val(res.data.description);
                        categoryForm.find('[name="display_order"]').val(res.data.display_order);

                        categoryModal.find('.modal-title').text('Düzenle');
                        categoryModal.modal('show')
                    },
                    error: function(xhr) {
                        swal.error({
                            message: xhr.responseJSON?.message ?? null
                        })
                    }
                })
            })

            $(document).on('click', '.categoryBtn', function() {
                categoryForm.submit()
            })

            $(document).on("submit", "#categoryForm", function(e) {
                e.preventDefault();

                let formData = new FormData(this);

                if (categoryForm.find('.image-input').hasClass('image-input-changed')) {
                    formData.append('logo_changed', 1);
                }
                $.ajax({
                    type: 'POST',
                    url: categorySubmitUrl,
                    data: formData,
                    dataType: 'json',
                    contentType: false,
                    processData: false,
                    cache: false,
                    beforeSend: function() {
                        propSubmitButton(categorySubmitBtn, 1);
                    },
                    success: function(res) {
                        swal.success({
                            message: res.message
                        })

                        dataTable.draw();

                        categoryModal.modal('hide')
                    },
                    error: function(xhr) {
                        swal.error({
                            message: xhr.responseJSON?.message ?? null
                        })
                    },
                    complete: function() {
                        propSubmitButton(categorySubmitBtn, 0);
                    }
                })
            })

            $(document).on('click', '#categorySubmitBtn', function() {
                categoryForm.submit()
            })
        })
    </script>
@endsection
