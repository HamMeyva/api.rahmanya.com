@extends('admin.template')
@use('App\Helpers\CommonHelper')
@section('title', (new CommonHelper())->limitText($stream->channel_name))
@section('breadcrumb')
<x-admin.breadcrumb :data="[(new CommonHelper())->limitText($stream->channel_name), 'Canlı Yayınlar' => route('admin.live-streams.index')]" :backUrl="route('admin.live-streams.index')" />
@endsection
@section('master')
<!--begin::Layout-->
<div class="d-flex flex-column flex-xl-row">
    <!--begin::Sidebar-->
    <div class="flex-column flex-lg-row-auto w-100 w-xl-350px">
        <div class="card mb-4 mb-xl-7">
            <div class="card-body d-flex justify-content-between gap-1">
                @if (!$stream->is_online)
                    <div class="d-flex align-items-center">
                        <i class="fa fa-info-circle fs-2" data-bs-toggle="tooltip" data-bs-placement="top" title="Yayın pasif olduğu için buttonlar devre dışı."></i>
                    </div>
                @endif
                <div>
                    <button class="btn btn-danger btn-sm" onclick="stopStream(this)" {{ !$stream->is_online ? 'disabled' : '' }} >Yayını Durdur</button>
                </div>
                <div>
                    <button class="btn btn-primary btn-sm" onclick="sendMessage(this)" {{ !$stream->is_online ? 'disabled' : '' }} >Yayına Mesaj Gönder</button>
                </div>
            </div>
        </div>
        <!--begin::Card-->
        <div class="card mb-5 mb-xl-8">
            <!--begin::Card body-->
            <div class="card-body d-flex flex-center p-5" style="height: 300px;">
                @if ($stream->thumbnail_url)
                    <img src="{{ $stream->thumbnail_url }}" alt="{{ $stream->channel_name }}">
                @else
                    <div>
                        <em class="fw-semibold fs-7">Kapak Fotoğrafı Yok</em>
                    </div>
                @endif
            </div>
            <!--end::Card body-->
        </div>
        <!--end::Card-->
    </div>
    <!--end::Sidebar-->

    <!--begin::Content-->
    <div class="flex-lg-row-fluid ms-lg-15">
        <!--begin:::Tabs-->
        <ul class="nav nav-custom nav-tabs nav-line-tabs nav-line-tabs-2x border-0 fs-4 fw-semibold mb-8">
            <!--begin:::Tab item-->
            <li class="nav-item">
                <a class="nav-link text-active-primary pb-4 active" data-bs-toggle="tab"
                    href="#kt_customer_view_overview_tab">Genel Bakış</a>
            </li>
            <!--end:::Tab item-->
            <!--begin:::Tab item-->
            <li class="nav-item">
                <a class="nav-link text-active-primary pb-4" data-bs-toggle="tab"
                    href="#kt_edit_tab">Düzenle</a>
            </li>
            <!--end:::Tab item-->

        </ul>
        <!--end:::Tabs-->

        <!--begin:::Tab content-->
        <div class="tab-content" id="myTabContent">
            <!--begin:::Tab pane-->
            <div class="tab-pane fade show active" id="kt_customer_view_overview_tab" role="tabpanel">
                <!--begin::Stat Cards-->
                <div class="row mb-10 g-5">
                    <div class="col-xl-6">
                        <div class="card bg-success">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <i class="fa fa-users-viewfinder me-auto fs-3x text-white"></i>
                                    <span class="text-gray-100 fw-bolder ms-auto fs-4 mb-2 mt-5">Görüntülenme</span>
                                </div>
                                <div class="text-end text-gray-100 fw-bolder fs-2">
                                    {{ (new CommonHelper)->formatNumber($stream->max_viewer_count ?? 0) }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <a href="{{ route('admin.report-problems.index') }}" class="card bg-danger">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <i class="fa fa-exclamation-circle me-auto fs-3x text-white"></i>
                                    <span class="text-gray-100 fw-bolder ms-auto fs-4 mb-2 mt-5">Şikayet</span>
                                </div>
                                <div class="text-end text-gray-100 fw-bolder fs-2">
                                    {{ (new CommonHelper)->formatNumber($reportCount) }}
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-xl-6">
                        <a href="{{ route('admin.agora-channel-gifts.index', ['stream_id' => $stream->id, 'stream_label' => $stream->channel_name]) }}" class="card bg-success">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <i class="fa fa-gift me-auto fs-3x text-white"></i>
                                    <span class="text-gray-100 fw-bolder ms-auto fs-4 mb-2 mt-5">Hediye Sayısı</span>
                                </div>
                                <div class="text-end text-gray-100 fw-bolder fs-2">
                                    {{ (new CommonHelper)->formatNumber($stream->total_gifts ?? 0) }}
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-xl-6">
                        <a href="{{ route('admin.agora-channel-gifts.index', ['stream_id' => $stream->id, 'stream_label' => $stream->channel_name]) }}" class="card bg-success">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <i class="fa fa-coins me-auto fs-3x text-white"></i>
                                    <span class="text-gray-100 fw-bolder ms-auto fs-4 mb-2 mt-5">Gönderilen Shoot Coin</span>
                                </div>
                                <div class="text-end text-gray-100 fw-bolder fs-2">
                                    {{ (new CommonHelper)->formatNumber($stream->total_coins_earned ?? 0) }}
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
                <!--end::Stat Cards-->
            </div>
            <!--end:::Tab pane-->
            <!--begin:::Tab pane-->
            <div class="tab-pane fade" id="kt_edit_tab" role="tabpanel">
                <!--begin::Card-->
                <div class="card pt-4 mb-6 mb-xl-9">
                    <!--begin::Card header-->
                    <div class="card-header border-0">
                        <!--begin::Card title-->
                        <div class="card-title">
                            Yayın Bilgilerini Düzenle
                        </div>
                        <!--end::Card title-->
                    </div>
                    <!--end::Cb header-->

                    <!--begin::Card body-->
                    <div class="card-body pt-0 pb-5">
                        <form id="editForm" class="row g-5">
                            @csrf
                            <div class="col-12">
                                <div class="mb-5">
                                    <label class="form-label">Başlık</label>
                                    <input type="text" class="form-control" name="title" value="{{$stream->title}}">
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="mb-5">
                                    <label class="form-label">Açıklama</label>
                                    <textarea class="form-control" name="description">{{$stream->description}}</textarea>
                                </div>
                            </div>


                            <div class="col-12">
                                <x-admin.form-elements.submit-btn class="mt-5">Değişiklikleri Kaydet</x-admin.form-elements.submit-btn>
                            </div>
                        </form>
                    </div>
                    <!--end::Card body-->
                </div>
                <!--end::Card-->
            </div>
            <!--end:::Tab pane-->
        </div>
        <!--end:::Tab content-->
    </div>
    <!--end::Content-->
</div>
<!--end::Layout-->
@endsection
@section('scripts')
<script>
    const stopStream = (button) => {
        button = $(button);

        Swal.fire({
            icon: 'warning',
            title: 'Yayını durdurmak istediğinize emin misiniz?',
            showConfirmButton: true,
            showCancelButton: true,
            allowOutsideClick: false,
            buttonsStyling: false,
            confirmButtonText: 'Durdur',
            cancelButtonText: 'Vazgeç',
            customClass: {
                confirmButton: "btn btn-danger btn-sm",
                cancelButton: 'btn btn-secondary btn-sm'
            }
        }).then((r) => {
            if (r.isConfirmed) {
                $.ajax({
                    type: 'POST',
                    url: "{{ route('admin.live-streams.stop', ['id' => $stream->id]) }}",
                    dataType: 'json',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    beforeSend: function() {
                        propSubmitButton(button, 1);
                    },
                    success: function(res) {
                        swal.success({
                            message: res.message
                        }).then(() => window.location.reload())
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
    }

    const sendMessage = (button) => {
        button = $(button);

        Swal.fire({
            icon: 'question',
            title: 'Yayına yönetici mesajı gönderimi',
            input: 'text',
            inputPlaceholder: 'Göndermek istediğin mesajı yaz...',
            showCancelButton: true,
            confirmButtonText: 'Gönder',
            cancelButtonText: 'Vazgeç',
            allowOutsideClick: false,
            buttonsStyling: false,
            customClass: {
                confirmButton: "btn btn-primary btn-sm",
                cancelButton: 'btn btn-secondary btn-sm'
            },
            inputValidator: (value) => {
                if (!value) {
                    return 'Mesaj boş olamaz!';
                }
            }
        }).then((r) => {
            if (r.isConfirmed) {
                $.ajax({
                    type: 'POST',
                    url: "{{ route('admin.live-streams.send-message', ['id' => $stream->id]) }}",
                    dataType: 'json',
                    data: {
                        _token: '{{ csrf_token() }}',
                        message: r.value
                    },
                    beforeSend: function() {
                        propSubmitButton(button, 1);
                    },
                    success: function(res) {
                        swal.success({
                            message: res.message
                        });
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
    }


    $(document).ready(function() {
        /* start::Edit */
        $(document).on("submit", "#editForm", function(e) {
            e.preventDefault();


            const button = $(this).find("[type='submit']");
            $.ajax({
                type: 'POST',
                url: "{{ route('admin.live-streams.update', ['id' => $stream->id]) }}",
                data: new FormData(this),
                dataType: 'json',
                contentType: false,
                processData: false,
                cache: false,
                beforeSend: function() {
                    propSubmitButton(button, 1);
                },
                success: function(res) {
                    swal.success({
                        message: res.message
                    }).then(() => window.location.reload())
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
        })
        /* end::Edit */
    })
</script>
@endsection
