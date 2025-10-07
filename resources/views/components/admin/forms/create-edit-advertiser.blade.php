@use('App\Models\Ad\Advertiser')
@props(['init' => false])
<!-- init sadece jsyi sayfaya bir kere eklemek için ekleniyor-->
@if (!$init)
    <div class="row g-5 create-edit-advertiser">
        @isset($advertiser)
            <div class="col-12">
                <label class="form-label required">Durum</label>
                <x-admin.form-elements.advertiser-status-select name="status_id" :selectedOption="$advertiser->status_id" :hideSearch="true" />
            </div>
        @endisset
        <div class="col-xl-12">
            <label class="form-label required">Logo</label>
            <div class="mb-2">
                <img id="logoPreview" class="rounded-3"
                    src="{{ isset($advertiser) && $advertiser->logo ? $advertiser->logo : assetAdmin('media/svg/avatars/blank.svg') }}"
                    alt="Logo Ön İzleme" style="max-height: 100px;">
            </div>
            <div class="d-flex align-items-center gap-2">
                <input type="file" name="logo" class="form-control" onchange="previewLogo(this)" accept="image/*">
            </div>
        </div>
        <div class="col-12">
            <div class="row" data-kt-buttons="true" data-kt-buttons-target="[data-kt-button='true']">
                <div class="col-xl-6">
                    <!--begin::Option-->
                    <label
                        class="btn btn-outline btn-outline-dashed btn-active-light-primary d-flex text-start p-6 {{ isset($advertiser) && $advertiser->type_id == Advertiser::TYPE_INDIVIDUAL ? 'active' : '' }} {{ !isset($advertiser) ? 'active' : '' }}"
                        data-kt-button="true">
                        <!--begin::Radio-->
                        <span
                            class="form-check form-check-custom form-check-solid form-check-sm align-items-start mt-1">
                            <input class="form-check-input" type="radio" name="type_id"
                                {{ isset($advertiser) && $advertiser->type_id == Advertiser::TYPE_INDIVIDUAL ? 'checked' : '' }}
                                {{ !isset($advertiser) ? 'checked' : '' }} value="{{ Advertiser::TYPE_INDIVIDUAL }}" />
                        </span>
                        <!--end::Radio-->
                        <!--begin::Info-->
                        <span class="ms-5">
                            <span
                                class="fs-4 fw-bold text-gray-800 d-block">{{ Advertiser::$types[Advertiser::TYPE_INDIVIDUAL] }}</span>
                        </span>
                        <!--end::Info-->
                    </label>
                    <!--end::Option-->
                </div>
                <div class="col-xl-6">
                    <!--begin::Option-->
                    <label
                        class="btn btn-outline btn-outline-dashed btn-active-light-primary d-flex text-start p-6 {{ isset($advertiser) && $advertiser->type_id == Advertiser::TYPE_CORPORATE ? 'active' : '' }}"
                        data-kt-button="true">
                        <!--begin::Radio-->
                        <span
                            class="form-check form-check-custom form-check-solid form-check-sm align-items-start mt-1">
                            <input class="form-check-input" type="radio" name="type_id"
                                value="{{ Advertiser::TYPE_CORPORATE }}"
                                {{ isset($advertiser) && $advertiser->type_id == Advertiser::TYPE_CORPORATE ? 'checked' : '' }} />
                        </span>
                        <!--end::Radio-->
                        <!--begin::Info-->
                        <span class="ms-5">
                            <span
                                class="fs-4 fw-bold text-gray-800 d-block">{{ Advertiser::$types[Advertiser::TYPE_CORPORATE] }}</span>
                        </span>
                        <!--end::Info-->
                    </label>
                    <!--end::Option-->
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <label
                class="form-label required">{{ isset($advertiser) && $advertiser->type_id == Advertiser::TYPE_CORPORATE ? 'Şirket Adı' : 'Ad Soyad' }}</label>
            <input name="name" class="form-control" value="{{ $advertiser->name ?? null }}">
        </div>
        <div class="col-xl-6">
            <label class="form-label required">E-Posta</label>
            <input name="email" class="form-control" value="{{ $advertiser->email ?? null }}">
        </div>
        <div class="col-12">
            <label class="form-label required">Telefon</label>
            <input name="phone" class="form-control" value="{{ $advertiser->phone ?? null }}">
        </div>
        <div class="col-12">
            <label class="form-label required">Adres</label>
            <textarea name="address" class="form-control" rows="2">{!! $advertiser->address ?? null !!}</textarea>
        </div>
    </div>
@endif
@push('scripts')
    <!-- bu dosyayı elden geçiricem istediğim gibi olmadı amacım sadece componenti çağırıp edit create işlemi oto yapılması -->
    <script>
        $(document).ready(function() {
            $(document).on('change', '.create-edit-advertiser [name="type_id"]', function() {
                $('.create-edit-advertiser [name="type_id"]').closest('label').removeClass('active');
                $(this).closest('label').addClass('active');

                if ($(this).val() == {{ Advertiser::TYPE_INDIVIDUAL }}) {
                    $('.create-edit-advertiser [name="name"]').parent().find('label').text('Ad Soyad');
                } else {
                    $('.create-edit-advertiser [name="name"]').parent().find('label').text('Şirket Adı');
                }
            })
        })

        function previewLogo(input) {
            const preview = document.getElementById('logoPreview');
            const file = input.files[0];

            if (file) {
                const reader = new FileReader();

                reader.onload = function(e) {
                    preview.src = e.target.result;
                };

                reader.readAsDataURL(file);
            }
        }
    </script>
@endpush
