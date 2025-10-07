@extends('admin.template')

@use('App\Models\Relations\Team')

@section('title', isset($gift) ? $gift->name : 'Hediye Ekle')
@section('breadcrumb')
    <x-admin.breadcrumb :data="isset($gift) ? $gift->name : 'Hediye Ekle'" :backUrl="route('admin.gifts.index')" />
@endsection
@section('styles')
    <style>
        .select-disabled {
            pointer-events: none;
            background-color: #f4f4f4 !important;
            color: #212529 !important;
            opacity: 1 !important;
            cursor: not-allowed;
        }
    </style>
@endsection
@section('master')
    <form id="primaryForm" class="form"
        action="{{ isset($gift) ? route('admin.gifts.update', ['gift' => $gift->id]) : route('admin.gifts.store') }}">
        @csrf
        <div class="card card-flush py-4">
            <!--begin::Card body-->
            <div class="card-body row g-3">
                <div class="col-12">
                    <h2>Genel</h2>
                </div>
                <div class="col-12">
                    <label class="form-label required">Durum</label>
                    <label class="form-check form-switch form-check-custom">
                        <input class="form-check-input " type="checkbox" name="is_active" value="1"
                            {{ isset($gift) ? ($gift->is_active ? 'checked' : '') : 'checked' }} />
                        <span class="form-check-label">
                            Aktif
                        </span>
                    </label>
                </div>
                <div class="col-12">
                    <label class="form-label required">Hediye Adı</label>
                    <input type="text" name="name" class="form-control mb-2" value="{{ @$gift?->name ?? null }}">
                </div>
                <div class="col-xl-6">
                    <label class="form-label required">Ücret</label>
                    <input type="number" name="price" class="form-control mb-2" value="{{ @$gift?->price ?? null }}"
                        min="1" step="1">
                    <div class="text-muted fs-7">Shoot Coin</div>
                </div>
                <div class="col-xl-6">
                    <label class="form-label">İndirimli Ücret</label>
                    <input type="number" name="discounted_price" class="form-control mb-2"
                        value="{{ @$gift?->discounted_price ?? null }}" min="1" step="1">
                    <div class="text-muted fs-7">İndirimli ücreti boş bırakırsanız indirim uygulanmayacaktır.
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label">Sıralama</label>
                    <input type="number" name="queue" class="form-control mb-2" value="{{ @$gift?->queue ?? null }}">
                </div>
                <div class="col-12">
                    <div class="separator separator-dashed my-5"></div>
                </div>
                <div class="col-12">
                    <h2>Görsel - Video</h2>
                </div>
                <div class="col-12">
                    <div class="alert alert-primary mb-0">
                        <div class="alert-text">
                            <strong>Not:</strong> Takımlara özel yükleme yapabilir veya takımlardan bağımsız ekleme
                            yapabilirsiniz.
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <!--begin::Repeater-->
                    <div id="asset_repeater_area">
                        <!--begin::Form group-->
                        <div class="form-group">
                            <div data-repeater-list="asset_repeater_area">
                                <div class="d-none">
                                    <div class="form-group row mt-3" data-repeater-item>
                                        <div class="col-lg-4">
                                            <label class="form-label mb-0">Takım</label>
                                            <select class="form-select" name="team_id" data-kt-repeater="select2"
                                                data-placeholder="&nbsp">
                                                <option></option>
                                                @foreach (Team::all() as $option)
                                                    <option value="{{ $option->id }}">{{ $option->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-lg-4">
                                            <label class="form-label mb-1 d-flex justify-content-between">
                                                Görsel
                                            </label>
                                            <input type="file" class="form-control" name="image_path"
                                                accept=".png, .jpg, .jpeg">
                                        </div>
                                        <div class="col-lg-4 d-flex gap-3 align-items-end">
                                            <div>
                                                <label class="form-label mb-1 d-flex justify-content-between">
                                                    Video
                                                </label>
                                                <input type="file" class="form-control" name="video_path" accept=".mp4">
                                            </div>
                                            <div>
                                                <a href="javascript:;" data-repeater-delete
                                                    class="btn btn-sm btn-light-danger mb-2">
                                                    <i class="ki-duotone ki-trash fs-5"><span class="path1"></span><span
                                                            class="path2"></span><span class="path3"></span><span
                                                            class="path4"></span><span class="path5"></span></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @if (isset($gift->assets) && $gift->assets->isNotEmpty())
                                    @foreach ($gift->assets as $asset)
                                        <div class="form-group row mt-3" data-repeater-item>
                                            <div class="col-lg-4">
                                                <label class="form-label mb-0">Takım</label>
                                                <select class="form-select select-disabled" name="team_id" data-kt-repeater="select2" data-placeholder="&nbsp">
                                                    <option></option>
                                                    @foreach (Team::all() as $option)
                                                        <option value="{{ $option->id }}"
                                                            {{ $asset->team_id === $option->id ? 'selected' : '' }}>
                                                            {{ $option->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-lg-4">
                                                <label class="form-label mb-1 d-flex justify-content-between">
                                                    Görsel
                                                    @if ($asset->image_url)
                                                        <a href="{{ $asset->image_url }}" target="_blank"
                                                            class="badge bg-success text-white text-decoration-none">
                                                            Görüntüle
                                                        </a>
                                                    @endif
                                                </label>
                                                <input type="file" class="form-control" name="image_path"
                                                    accept=".png, .jpg, .jpeg">
                                            </div>
                                            <div class="col-lg-4 d-flex gap-3 align-items-end">
                                                <div>
                                                    <label class="form-label mb-1 d-flex justify-content-between">
                                                        Video
                                                        @if ($asset->video_url)
                                                            <a href="{{ $asset->video_url }}" target="_blank"
                                                                class="badge bg-success text-white text-decoration-none">
                                                                Görüntüle
                                                            </a>
                                                        @endif
                                                    </label>
                                                    <input type="file" class="form-control" name="video_path"
                                                        accept=".mp4">
                                                </div>
                                                <div>
                                                    <a href="javascript:;" data-repeater-delete
                                                        class="btn btn-sm btn-light-danger mb-2">
                                                        <i class="ki-duotone ki-trash fs-5"><span
                                                                class="path1"></span><span class="path2"></span><span
                                                                class="path3"></span><span class="path4"></span><span
                                                                class="path5"></span></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                @endif
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
                </div>
                <div class="col-12 text-end">
                    <x-admin.form-elements.submit-btn>Kaydet</x-admin.form-elements.submit-btn>
                </div>
            </div>
            <!--end::Card body-->
        </div>
    </form>
@endsection
@section('scripts')
    <script src="{{ assetAdmin('plugins/custom/formrepeater/formrepeater.bundle.js') }}"></script>
    <script>
        $('#asset_repeater_area').repeater({
            initEmpty: false,

            show: function() {
                $(this).slideDown();
                $(this).find('[data-kt-repeater="select2"]').select2();
            },

            hide: function(deleteElement) {
                $(this).slideUp(deleteElement);
            },

            ready: function() {
                $('[data-kt-repeater="select2"]').select2();
            }
        });
    </script>
    <script>
        $(document).ready(function() {
            $(document).on("submit", "#primaryForm", function(e) {
                e.preventDefault();

                let formData = new FormData(this),
                    submitBtn = $(this).find('button[type="submit"]'),
                    url = $(this).attr('action');

                if ($(this).find('.image-input').hasClass('image-input-changed')) {
                    formData.append('image_changed', 1);
                }

                $.ajax({
                    type: 'POST',
                    url: url,
                    data: formData,
                    dataType: 'json',
                    contentType: false,
                    processData: false,
                    cache: false,
                    beforeSend: function() {
                        propSubmitButton(submitBtn, 1);
                    },
                    success: function(res) {
                        swal.success({
                            message: res.message
                        }).then(() => window.location.href = res?.redirect)
                    },
                    error: function(xhr) {
                        swal.error({
                            message: xhr.responseJSON?.message ?? null
                        })
                    },
                    complete: function() {
                        propSubmitButton(submitBtn, 0);
                    }
                })
            })
        })
    </script>
@endsection
