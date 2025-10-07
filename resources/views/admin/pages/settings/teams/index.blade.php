@extends('admin.template')
@section('title', 'Takımlar')
@section('breadcrumb')
    <x-admin.breadcrumb data="Takımlar" />
@endsection
@section('master')
    <!--begin::Card-->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <x-admin.form-elements.search-input attr="data-table-action=search" />
            </div>
            <div class="card-toolbar flex-row-fluid justify-content-end gap-5">
                <button class="btn btn-primary addTeamBtn">
                    <i class="fa fa-plus"></i> Ekle
                </button>
            </div>
        </div>
        <div class="card-body">
            <x-admin.data-table tableId="dataTable">
                <x-slot name="header">
                    <th class="mw-20px">#</th>
                    <th>Adı</th>
                    <th>Renk</th>
                    <th>Logo</th>
                    <th>İşlemler</th>
                </x-slot>
            </x-admin.data-table>
        </div>
    </div>
    <!--end::Card-->

    <!--start::Modals-->
    <x-admin.modals.index id='teamModal'>
        <form id='teamForm'>
            @csrf
            <x-slot:title></x-slot>

            <div class="row g-5">
                <div class="col-xl-6">
                    <div class="fs-6 fw-semibold mt-2 mb-3">Logo</div>
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
                                title="Sil">
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
                        <label class="form-label">Takım Adı</label>
                        <input class="form-control" name="name" />
                    </div>
                    <div>
                        <label class="form-label">Renkler</label>
                        <div class="row">
                            <div class="col-xl-6">
                                <input type="color" class="form-control h-50px" name="color1" value="" />
                            </div>
                            <div class="col-xl-6">
                                <input type="color" class="form-control h-50px" name="color2" value="" />
                            </div>
                        </div>
                    </div>
                </div>
                <input type="submit" class="d-none">
            </div>

            <x-slot:footer>
                <x-admin.form-elements.submit-btn id="teamSubmitBtn">Kaydet</x-admin.form-elements.submit-btn>
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
                    },
                    {
                        orderable: false,
                        targets: 4
                    },
                ],
                "processing": true,
                "serverSide": true,
                "ajax": {
                    "url": "{{ route('admin.settings.teams.data-table') }}",
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


            const teamForm = $('#teamForm'),
                teamModal = $('#teamModal'),
                teamSubmitBtn = $('#teamSubmitBtn')

            let teamSubmitUrl;

            $(document).on('click', '.addTeamBtn', function() {
                teamForm.find('.image-input').addClass('image-input-empty');
                teamForm.find('.image-input-wrapper').css('background-image', 'none');
                teamForm.find('[name="name"]').val('');
                teamForm.find('[name="color1"]').val('');
                teamForm.find('[name="color2"]').val('');


                teamModal.find('.modal-title').text('Ekle');
                teamSubmitUrl = `{{ route('admin.settings.teams.store') }}`;
                teamModal.modal('show')
            })

            $(document).on('click', '.editTeamBtn', function() {
                let id = $(this).data('id'),
                    imageInput = teamForm.find('.image-input');

                $.ajax({
                    type: 'GET',
                    url: `{{ route('admin.settings.teams.show') }}/${id}`,
                    dataType: 'json',
                    success: function(res) {
                        teamSubmitUrl = `{{ route('admin.settings.teams.update') }}/${id}`;

                        if (res.data.logo) {
                            imageInput.removeClass('image-input-empty');
                            teamForm.find('.image-input-wrapper').css('background-image',
                                'url(' + res.data.logo + ')');
                        } else {
                            imageInput.addClass('image-input-empty');
                            teamForm.find('.image-input-wrapper').css('background-image',
                                'none');
                        }

                        teamForm.find('[name="name"]').val(res.data.name);
                        teamForm.find('[name="color1"]').val(res.data.colors.color1);
                        teamForm.find('[name="color2"]').val(res.data.colors.color2);

                        teamModal.find('.modal-title').text('Düzenle');
                        teamModal.modal('show')
                    },
                    error: function(xhr) {
                        swal.error({
                            message: xhr.responseJSON?.message ?? null
                        })
                    }
                })
            })

            $(document).on('click', '.teamBtn', function() {
                teamForm.submit()
            })

            $(document).on("submit", "#teamForm", function(e) {
                e.preventDefault();

                let formData = new FormData(this);

                if (teamForm.find('.image-input').hasClass('image-input-changed')) {
                    formData.append('logo_changed', 1);
                }
                $.ajax({
                    type: 'POST',
                    url: teamSubmitUrl,
                    data: formData,
                    dataType: 'json',
                    contentType: false,
                    processData: false,
                    cache: false,
                    beforeSend: function() {
                        propSubmitButton(teamSubmitBtn, 1);
                    },
                    success: function(res) {
                        swal.success({
                            message: res.message
                        })

                        dataTable.draw();

                        teamModal.modal('hide')
                    },
                    error: function(xhr) {
                        swal.error({
                            message: xhr.responseJSON?.message ?? null
                        })
                    },
                    complete: function() {
                        propSubmitButton(teamSubmitBtn, 0);
                    }
                })
            })

            $(document).on('click', '#teamSubmitBtn', function() {
                teamForm.submit()
            })
        })
    </script>
@endsection
