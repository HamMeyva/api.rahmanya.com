@extends('admin.template')

@use("App\Models\Common\Country")
@use("App\Models\Common\Currency")

@section('title', 'Shoot Coin Satış Paketleri')
@section('breadcrumb')
    <x-admin.breadcrumb data="Shoot Coin Satış Paketleri" />
@endsection
@section('master')
    <div class="card mb-5">
        <div class="card-body py-1">
            <div class="m-0">
                <!--begin::Heading-->
                <div class="d-flex align-items-center collapsible py-3 toggle mb-0 collapsed" data-bs-toggle="collapse"
                    data-bs-target="#kt_job_8_1" aria-expanded="false">
                    <!--begin::Icon-->
                    <div class="btn btn-sm btn-icon mw-20px btn-active-color-primary me-5">
                        <i class="ki-duotone ki-minus-square toggle-on text-primary fs-1">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                        <i class="ki-duotone ki-plus-square toggle-off fs-1">
                            <span class="path1"></span>
                            <span class="path2"></span>
                            <span class="path3"></span>
                        </i>
                    </div>
                    <!--end::Icon-->
                    <!--begin::Title-->
                    <h4 class="text-primary fw-bold cursor-pointer mb-0">Yeni Paketler Ekle</h4>
                    <!--end::Title-->
                </div>
                <!--end::Heading-->
                <!--begin::Body-->
                <div id="kt_job_8_1" class="fs-6 ms-1 collapse pb-5" style="">
                    <form id="addPackageForm">
                        @csrf
                        <!--begin::Repeater-->
                        <div id="package_repeater_condition_area">
                            <!--begin::Form group-->
                            <div class="form-group">
                                <div data-repeater-list="package_repeater_condition_area">
                                    <div class="form-group row mt-3" data-repeater-item>
                                        <div class="col-lg-2">
                                            <label class="form-label mb-0 required">Coin Miktarı</label>
                                            <input type="number" class="form-control" name="coin_amount" min="1" step="1" required>
                                        </div>
                                        <div class="col-lg-2">
                                            <label class="form-label mb-0 required">Fiyat</label>
                                            <input type="text" class="form-control price-input" name="price">
                                        </div>
                                        <div class="col-lg-2">
                                            <label class="form-label mb-0">İndirimli Fiyat <i class="fa fa-info-circle" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="İndirimli fiyatı boş bırakırsanız indirim uygulanmayacaktır."></i></label>
                                            <input type="text" class="form-control price-input" name="discounted_price">
                                        </div>
                                        <div class="col-lg-2">
                                            <label class="form-label mb-0 required">Para Birimi</label>
                                            <select class="form-select" name="currency_id" data-kt-repeater="select2"
                                                data-placeholder="&nbsp" required>
                                                <option></option>
                                                @foreach(Currency::all() as $option)
                                                    <option value="{{$option->id}}">{{$option->name}} - {{$option->symbol}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-lg-2">
                                            <label class="form-label mb-0 required">Ülke</label>
                                            <select class="form-select" name="country_id" data-kt-repeater="select2"
                                                data-placeholder="&nbsp" required>
                                                <option></option>
                                                @foreach(Country::orderByRaw("name = 'Turkey' DESC")->orderBy('name')->get() as $option)
                                                    <option value="{{$option->id}}" {{ old('country_id') == $option->id ? 'selected' : '' }}>{{$option->native}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-lg-1 d-flex align-items-center">
                                            <a href="javascript:;" data-repeater-delete
                                                class="btn btn-sm btn-light-danger mt-6">
                                                <i class="ki-duotone ki-trash fs-5"><span class="path1"></span><span
                                                        class="path2"></span><span class="path3"></span><span
                                                        class="path4"></span><span class="path5"></span></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!--end::Form group-->

                            <!--begin::Form group-->
                            <div class="form-group mt-5">
                                <a href="javascript:;" data-repeater-create class="btn btn-light-primary">
                                    <i class="ki-duotone ki-plus fs-3"></i>
                                    Satır Ekle
                                </a>
                            </div>
                            <!--end::Form group-->
                        </div>
                        <!--end::Repeater-->
                        <div class="text-end mt-5">
                            <x-admin.form-elements.submit-btn>Kaydet</x-admin.form-elements.submit-btn>
                        </div>
                    </form>
                </div>
                <!--end::Content-->
            </div>
        </div>
    </div>

    <!--begin::Card-->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <h3>Satış Paketleri</h3>
            </div>
            <div class="card-toolbar flex-row-fluid justify-content-end gap-5">
                <!-- --- -->
            </div>
        </div>
        <div class="card-body">
            <x-admin.data-table tableId="dataTable">
                <x-slot name="header">
                    <th class="mw-50px">#</th>
                    <th>Coin Miktarı</th>
                    <th>Fiyat</th>
                    <th>Para Birimi</th>
                    <th>Durum</th>
                    <th>Ülke</th>
                    <th>İşlemler</th>
                </x-slot>
            </x-admin.data-table>
        </div>
    </div>
    <!--end::Card-->

        <!--start::Modals-->
        <x-admin.modals.index id='editModal'>
            <form id='editPackageForm'>
                @csrf
                <x-slot:title>Düzenle</x-slot>
                <div class="row g-5">
                    <div class="col-xl-12">
                        <label class="form-label required">Durum</label>
                        <label class="form-check form-switch form-check-custom">
                            <input class="form-check-input " type="checkbox" name="is_active" value="1" />
                            <span class="form-check-label">
                                Aktif
                            </span>
                        </label>
                    </div>
                    <div class="col-xl-12">
                        <label class="form-label required">Coin Miktarı</label>
                        <input type="number" class="form-control" name="coin_amount" min="1" step="1" required>
                    </div>
                    <div class="col-xl-6">
                        <label class="form-label required">Fiyat</label>
                        <input type="text" class="form-control price-input" name="price">
                    </div>
                    <div class="col-xl-6">
                        <label class="form-label">İndirimli Fiyat <i class="fa fa-info-circle" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="İndirimli fiyatı boş bırakırsanız indirim uygulanmayacaktır."></i></label>
                        <input type="text" class="form-control price-input" name="discounted_price">
                    </div>
                    <div class="col-xl-6">
                        <label class="form-label required">Para Birimi</label>
                        <x-admin.form-elements.currency-select name="currency_id" />
                    </div>
                    <div class="col-xl-6">
                        <label class="form-label required">Ülke</label>
                        <x-admin.form-elements.country-select name="country_id" />
                    </div>
                    <input type="submit" class="d-none">
                </div>
                <x-slot:footer>
                    <x-admin.form-elements.submit-btn id="editSubmitBtn">Kaydet</x-admin.form-elements.submit-btn>
                </x-slot>
            </form>
        </x-admin.modals.index>
        <!--end::Modals-->
@endsection
@section('scripts')
    <script src="{{assetAdmin('plugins/custom/formrepeater/formrepeater.bundle.js')}}"></script>
    <script>
        $('#package_repeater_condition_area').repeater({
            initEmpty: false,

            show: function () {
                $(this).slideDown();
                $(this).find('[data-kt-repeater="select2"]').select2();
            },

            hide: function (deleteElement) {
                $(this).slideUp(deleteElement);
            },

            ready: function () {
                $('[data-kt-repeater="select2"]').select2();
            }
        });
    </script>
    <script>
        $(document).ready(function () {
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
                    orderable: false,
                    targets: 6
                }
                ],
                "processing": true,
                "serverSide": true,
                "ajax": {
                    "url": "{{ route('admin.coin-packages.data-table') }}",
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

            $(document).on("submit", "#addPackageForm", function(e) {
                e.preventDefault();
                let formData = new FormData(this),
                    btn = $(this).find('[type="submit"]');

                $.ajax({
                    type: 'POST',
                    url: "{{route('admin.coin-packages.store')}}",
                    data: formData,
                    dataType: 'json',
                    contentType: false,
                    processData: false,
                    cache: false,
                    beforeSend: function() {
                        propSubmitButton(btn, 1);
                    },
                    success: function(res) {
                        swal.success({
                            message: res.message
                        }).then(()=> window.location.reload())
                    },
                    error: function(xhr) {
                        swal.error({
                            message: xhr.responseJSON?.message ?? null
                        })
                    },
                    complete: function() {
                        propSubmitButton(btn, 0);
                    }
                })
            })


            const editForm = $("#editPackageForm");
            let itemId;
            $(document).on('click', '.editBtn', function () {
                itemId = $(this).data('id');

                $.ajax({
                    type: 'GET',
                    url: `{{ route('admin.coin-packages.show') }}/${itemId}`,
                    dataType: 'json',
                    success: function (res) {
                        submitUrl = `{{ route('admin.coin-packages.update') }}/${itemId}`;

                        editForm.find('[name="is_active"]').prop('checked', res.data.is_active);
                        editForm.find('[name="coin_amount"]').val(res.data.coin_amount);
                        editForm.find('[name="price"]').val(res.data.get_price);
                        editForm.find('[name="discounted_price"]').val(res.data.discounted_price ? res.data.draw_discounted_price : null);
                        editForm.find('[name="currency_id"]').val(res.data.currency_id).trigger('change');
                        editForm.find('[name="country_id"]').val(res.data.country_id).trigger('change');


                        console.log(res.data, 'xx');
                        
                        $("#editModal").modal('show')
                    },
                    error: function (xhr) {
                        swal.error({
                            message: xhr.responseJSON?.message ?? null
                        })
                    }
                })
            })

            $(document).on("submit", "#editPackageForm", function(e) {
                e.preventDefault();
                let formData = new FormData(this),
                    btn = $(this).find('[type="submit"]');

                $.ajax({
                    type: 'POST',
                    url: `{{ route('admin.coin-packages.update') }}/${itemId}`,
                    data: formData,
                    dataType: 'json',
                    contentType: false,
                    processData: false,
                    cache: false,
                    beforeSend: function() {
                        propSubmitButton(btn, 1);
                    },
                    success: function(res) {
                        swal.success({
                            message: res.message
                        }).then(()=> window.location.reload())
                    },
                    error: function(xhr) {
                        swal.error({
                            message: xhr.responseJSON?.message ?? null
                        })
                    },
                    complete: function() {
                        propSubmitButton(btn, 0);
                    }
                })
            })

            $(document).on('click', '#editSubmitBtn', function () {
                editForm.submit()
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
                            url: `{{ route('admin.coin-packages.destroy') }}/${id}`,
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
        })
    </script>
@endsection