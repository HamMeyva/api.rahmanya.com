@extends('admin.template')
@section('title', 'Popüler Aramalar')
@section('breadcrumb')
    <x-admin.breadcrumb data="Popüler Aramalar" />
@endsection
@section('master')
    <!--begin::Card-->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <x-admin.form-elements.search-input attr="data-table-action=search" />
            </div>
            <div class="card-toolbar flex-row-fluid justify-content-end gap-5">
                <button class="btn btn-primary addPopularSearchBtn">
                    <i class="fa fa-plus"></i> Ekle
                </button>
            </div>
        </div>
        <div class="card-body">
            <x-admin.data-table tableId="dataTable">
                <x-slot name="header">
                    <th>Görsel</th>
                    <th>Başlık</th>
                    <th>Durum</th>
                    <th>Sıra</th>
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
                    <div class="col-12">
                        <label class="form-label required">Görsel</label>
                        <!--begin::Image input-->
                        <div>
                            <div class="image-input image-input-outline image-input-empty" data-kt-image-input="true"
                                style="background-image: url('{{ assetAdmin('media/svg/avatars/blank.svg') }}')">
                                <!--begin::Preview existing avatar-->
                                <div class="image-input-wrapper w-125px h-125px"></div>
                                <!--end::Preview existing avatar-->

                                <!--begin::Label-->
                                <label class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                    data-kt-image-input-action="change">
                                    <i class="ki-duotone ki-pencil fs-7"><span class="path1"></span><span
                                            class="path2"></span></i>
                                    <!--begin::Inputs-->
                                    <input type="file" name="image" accept=".png, .jpg, .jpeg" />
                                    <input type="hidden" name="remove_image" />
                                    <!--end::Inputs-->
                                </label>
                                <!--end::Label-->
                            </div>
                        </div>
                        <!--end::Image input-->
                    </div>
                    <div class="col-12">
                        <label class="form-label required">Durum</label>
                        <label class="form-check form-switch form-check-custom">
                            <input class="form-check-input " type="checkbox" name="is_active" value="1" checked />
                            <span class="form-check-label">
                                Aktif
                            </span>
                        </label>
                    </div>
                    <div class="col-xl-6">
                        <label class="form-label required">Başlık</label>
                        <input class="form-control" name="title" />
                    </div>

                    <div class="col-xl-6">
                        <label class="form-label">Sıra</label>
                        <input class="form-control" name="queue" type="number" min="1" />
                    </div>
                    <x-slot:footer>
                        <x-admin.form-elements.submit-btn id="primarySubmitBtn">Kaydet</x-admin.form-elements.submit-btn>
                    </x-slot:footer>
        </form>
    </x-admin.modals.index>
    <!--end::Modals-->
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
                    "url": "{{ route('admin.settings.popular-searches.data-table') }}",
                    "type": "POST",
                    "data": function (d) {
                        d._token = "{{ csrf_token() }}"
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

            const primaryForm = $('#primaryForm'),
                primaryModal = $('#primaryModal'),
                primarySubmitBtn = $('#primarySubmitBtn')

            let primarySubmitUrl;

            $(document).on('click', '.addPopularSearchBtn', function () {
                resetPrimaryForm();

                primaryModal.find('.modal-title').text('Ekle');
                primarySubmitUrl = `{{ route('admin.settings.popular-searches.store') }}`;
                primaryModal.modal('show')
            })

            $(document).on('click', '.editPopularSearchBtn', function () {
                let id = $(this).data('id'),
                    imageInput = primaryForm.find('.image-input');

                $.ajax({
                    type: 'GET',
                    url: `{{ route('admin.settings.popular-searches.show') }}/${id}`,
                    dataType: 'json',
                    success: function (res) {
                        primarySubmitUrl = `{{ route('admin.settings.popular-searches.update') }}/${id}`;

                        imageInput.removeClass('image-input-empty');
                        primaryForm.find('.image-input-wrapper').css('background-image',
                            'url(' + res.data.image_url + ')');

                        primaryForm.find('[name="title"]').val(res.data.title);
                        primaryForm.find('[name="queue"]').val(res.data.queue);
                        primaryForm.find('[name="is_active"]').prop('checked', res.data.is_active);

                        primaryModal.find('.modal-title').text('Düzenle');
                        primaryModal.modal('show')
                    },
                    error: function (xhr) {
                        swal.error({
                            message: xhr.responseJSON?.message ?? null
                        })
                    }
                })
            })

            $(document).on("submit", "#primaryForm", function (e) {
                e.preventDefault();
                let formData = new FormData(this);

                if (primaryForm.find('.image-input').hasClass('image-input-changed')) {
                    formData.append('image_changed', 1);
                }
                
                $.ajax({
                    type: 'POST',
                    url: primarySubmitUrl,
                    data: formData,
                    dataType: 'json',
                    contentType: false,
                    processData: false,
                    cache: false,
                    beforeSend: function () {
                        propSubmitButton(primarySubmitBtn, 1);
                    },
                    success: function (res) {
                        swal.success({
                            message: res.message
                        })

                        dataTable.draw();

                        primaryModal.modal('hide')
                    },
                    error: function (xhr) {
                        swal.error({
                            message: xhr.responseJSON?.message ?? null
                        })
                    },
                    complete: function () {
                        propSubmitButton(primarySubmitBtn, 0);
                    }
                })
            })

            $(document).on('click', '#primarySubmitBtn', function () {
                primaryForm.submit()
            })

            $(document).on('click', '.deleteBtn', function () {
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
                            url: `{{ route('admin.settings.popular-searches.destroy') }}/${id}`,
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            dataType: 'json',
                            success: function (res) {
                                dataTable.draw()
                                swal.success({
                                    message: res.message
                                })
                            },
                            error: function (xhr) {
                                swal.error({
                                    message: xhr?.responseJSON?.message ?? null
                                })
                            }
                        })
                    }
                })
            })

            const resetPrimaryForm = () => {
                primaryForm.find('.image-input').addClass('image-input-empty');
                primaryForm.find('.image-input-wrapper').css('background-image', 'none');
                primaryForm.find('[name="title"]').val('');
                primaryForm.find('[name="queue"]').val('');
                primaryForm.find('[name="is_active"]').prop('checked', true);
            }
        })
    </script>
@endsection