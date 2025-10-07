@extends('admin.template')
@use('App\Helpers\CommonHelper')
@section('title', (new CommonHelper())->limitText($video->description))
@section('breadcrumb')
<x-admin.breadcrumb :data="[(new CommonHelper())->limitText($video->description), 'Videolar' => route('admin.videos.index')]" :backUrl="route('admin.videos.index')" />
@endsection
@section('styles')
<style>
    div[data-content-area].loading {
        filter: blur(1px);
        opacity: 0.3;
        pointer-events: none;
    }

    [data-loading] {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        z-index: 2;
    }
</style>
@endsection
@section('master')
@if ($video->deleted_at)
<div class="alert alert-danger">
    Bu video {{ $video->get_deleted_at }} tarihinde silinmiştir.
</div>
@endif
<!--begin::Layout-->
<div class="d-flex flex-column flex-xl-row">
    <!--begin::Sidebar-->
    <div class="flex-column flex-lg-row-auto w-100 w-xl-350px">
        <!--begin::Card-->
        <div class="card mb-5 mb-xl-8">
            <!--begin::Card body-->
            <div class="card-body p-0" style="height: 600px;">
                <div style="position: relative;">
                    <iframe
                        src="https://iframe.mediadelivery.net/embed/410164/{{ $video->video_guid }}?autoplay=true&loop=true"
                        loading="lazy"
                        style="border-radius: 10px; position: absolute; top: 0; height: 600px; width: 100%"
                        allow="accelerometer; gyroscope; autoplay; encrypted-media; picture-in-picture;"
                        allowfullscreen="true">
                    </iframe>
                </div>
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
            <!--begin:::Tab item-->
            <li class="nav-item">
                <a class="nav-link text-active-primary pb-4" data-bs-toggle="tab"
                    href="#kt_views_tab">Görüntülenmeler</a>
            </li>
            <!--end:::Tab item-->
            <!--begin:::Tab item-->
            <li class="nav-item">
                <a class="nav-link text-active-primary pb-4" data-bs-toggle="tab" href="#kt_likes_tab">Beğeniler</a>
            </li>
            <!--end:::Tab item-->
            <!--begin:::Tab item-->
            <li class="nav-item">
                <a class="nav-link text-active-primary pb-4" data-bs-toggle="tab" href="#kt_comments_tab">Yorumlar</a>
            </li>
            <!--end:::Tab item-->
            <!--begin:::Tab item-->
            <li class="nav-item">
                <a class="nav-link text-active-primary pb-4" data-bs-toggle="tab" href="#kt_report_problems_tab">Şikayetler</a>
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
                        <a href="#" class="card bg-success">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <i class="fa fa-users-viewfinder me-auto fs-3x text-white"></i>
                                    <span class="text-gray-100 fw-bolder ms-auto fs-4 mb-2 mt-5">Görüntülenme</span>
                                </div>
                                <div class="text-end text-gray-100 fw-bolder fs-2">
                                    {{ $story->views_count ?? 0 }}
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-xl-6">
                        <a href="#" class="card bg-success">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <i class="fa fa-heart me-auto fs-3x text-white"></i>
                                    <span class="text-gray-100 fw-bolder ms-auto fs-4 mb-2 mt-5">Beğeni</span>
                                </div>
                                <div class="text-end text-gray-100 fw-bolder fs-2">
                                    {{ $story->likes_count ?? 0 }}
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-xl-6">
                        <a href="#" class="card bg-danger">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <i class="fa fa-exclamation-circle me-auto fs-3x text-white"></i>
                                    <span class="text-gray-100 fw-bolder ms-auto fs-4 mb-2 mt-5">Yorum</span>
                                </div>
                                <div class="text-end text-gray-100 fw-bolder fs-2">
                                    0
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-xl-6">
                        <a href="#" class="card bg-danger">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <i class="fa fa-exclamation-circle me-auto fs-3x text-white"></i>
                                    <span class="text-gray-100 fw-bolder ms-auto fs-4 mb-2 mt-5">Şikayet</span>
                                </div>
                                <div class="text-end text-gray-100 fw-bolder fs-2">
                                    0
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
                <!--end::Stat Cards-->

                <!--begin::Card-->
                <div class="card pt-4 mb-6 mb-xl-9">
                    <!--begin::Card header-->
                    <div class="card-header border-0">
                        <!--begin::Card title-->
                        <div class="card-title">
                            <h2>Beğeni - Yorum Analizi</h2>
                        </div>
                        <!--end::Card title-->
                        <div class="card-toolbar d-flex gap-3">
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle" style="width: 16px; height: 16px; background-color: #ff3366;">
                                </div>
                                <small class="text-muted">Beğeni</small>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle" style="width: 16px; height: 16px; background-color: #6c757d;">
                                </div>
                                <small class="text-muted">Yorum</small>
                            </div>
                        </div>
                    </div>
                    <!--end::Card header-->

                    <!--begin::Card body-->
                    <div class="card-body position-relative loading" data-content-area="like-comment">
                        <!-- begin::Header-->
                        <div data-loading style="display: none;">
                            <span class="spinner-border spinner-border-sm align-middle text-primary border-2"></span>
                        </div>
                        <!--begin::Chart-->
                        <div id="likeCommentChart" class="h-250px"></div>
                        <!--end::Chart-->
                    </div>
                    <!--end::Card body-->
                </div>
                <!--end::Card-->
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
                            Video Bilgilerini Düzenle
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
                                    <label class="form-label">Açıklama</label>
                                    <textarea class="form-control" name="description">{{$video->description}}</textarea>
                                </div>
                            </div>
                            <div class="col-xl-3">
                                <label class="form-label">Gizli</label>
                                <label class="form-check form-switch form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" value="1" name="is_private" {{ $video->is_private ? 'checked' : '' }}>
                                    <span class="form-check-label fw-semibold text-muted">
                                        Aktif
                                    </span>
                                </label>
                            </div>
                            <div class="col-xl-3">
                                <label class="form-label">Yorum Yapılabilir</label>
                                <label class="form-check form-switch form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" value="1" name="is_commentable" {{ $video->is_commentable ? 'checked' : '' }}>
                                    <span class="form-check-label fw-semibold text-muted">
                                        Aktif
                                    </span>
                                </label>
                            </div>
                            <div class="col-xl-3">
                                <label class="form-label">Öne Çıkan</label>
                                <label class="form-check form-switch form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" value="1" name="is_featured" {{ $video->is_featured ? 'checked' : '' }}>
                                    <span class="form-check-label fw-semibold text-muted">
                                        Aktif
                                    </span>
                                </label>
                            </div>
                            <div class="col-xl-3">
                                <label class="form-label">Spor Videosu</label>
                                <label class="form-check form-switch form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" value="1" name="is_sport" {{ $video->is_sport ? 'checked' : '' }}>
                                    <span class="form-check-label fw-semibold text-muted">
                                        Aktif
                                    </span>
                                </label>
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
            <!--begin:::Tab pane-->
            <div class="tab-pane fade" id="kt_views_tab" role="tabpanel">
                <!--begin::Card-->
                <div class="card pt-4 mb-6 mb-xl-9">
                    <!--begin::Card header-->
                    <div class="card-header border-0">
                        <!--begin::Card title-->
                        <div class="card-title">
                            <x-admin.form-elements.search-input attr="views-data-table-action=search"
                                class="form-control-sm" />
                        </div>
                        <!--end::Card title-->
                        <!--start::Card toolbar-->
                        <div class="card-toolbar flex-row-fluid justify-content-end gap-5">
                            <div class="w-100 mw-175px">
                                <x-admin.form-elements.user-select placeholder="Tüm Kullanıcılar"
                                    customClass="form-select-sm" customAttr="views-data-table-filter=user_id"
                                    :allowClear="true" />
                            </div>
                        </div>
                        <!--end::Card toolbar-->
                    </div>
                    <!--end::Card header-->

                    <!--begin::Card body-->
                    <div class="card-body pt-0 pb-5">
                        <x-admin.data-table tableId="viewsDataTable">
                            <x-slot name="header">
                                <th>Kullanıcı Adı</th>
                                <th>Gösterim Süresi</th>
                                <th>Tamamı İzlendi</th>
                                <th>IP Adresi</th>
                                <th>Cihaz</th>
                                <th>İşlem Tarihi</th>
                            </x-slot>
                        </x-admin.data-table>
                    </div>
                    <!--end::Card body-->
                </div>
                <!--end::Card-->
            </div>
            <!--end:::Tab pane-->
            <!--begin:::Tab pane-->
            <div class="tab-pane fade" id="kt_likes_tab" role="tabpanel">
                <!--begin::Card-->
                <div class="card pt-4 mb-6 mb-xl-9">
                    <!--begin::Card header-->
                    <div class="card-header border-0">
                        <!--begin::Card title-->
                        <div class="card-title">
                            <x-admin.form-elements.search-input attr="likes-data-table-action=search"
                                class="form-control-sm" />
                        </div>
                        <!--end::Card title-->
                        <!--start::Card toolbar-->
                        <div class="card-toolbar flex-row-fluid justify-content-end gap-5">
                            <div class="w-100 mw-175px">
                                <x-admin.form-elements.user-select placeholder="Tüm Kullanıcılar"
                                    customClass="form-select-sm" customAttr="likes-data-table-filter=user_id"
                                    :allowClear="true" />
                            </div>
                        </div>
                        <!--end::Card toolbar-->
                    </div>
                    <!--end::Card header-->

                    <!--begin::Card body-->
                    <div class="card-body pt-0 pb-5">
                        <x-admin.data-table tableId="likesDataTable">
                            <x-slot name="header">
                                <th>Kullanıcı Adı</th>
                                <th>Ad Soyad</th>
                                <th>İşlem Tarihi</th>
                            </x-slot>
                        </x-admin.data-table>
                    </div>
                    <!--end::Card body-->
                </div>
                <!--end::Card-->
            </div>
            <!--end:::Tab pane-->
            <!--begin:::Tab pane-->
            <div class="tab-pane fade" id="kt_comments_tab" role="tabpanel">
                <!--begin::Card-->
                <div class="card pt-4 mb-6 mb-xl-9">
                    <!--begin::Card header-->
                    <div class="card-header border-0">
                        <!--begin::Card title-->
                        <div class="card-title">
                            <x-admin.form-elements.search-input attr="comments-data-table-action=search"
                                class="form-control-sm" />
                        </div>
                        <!--end::Card title-->
                        <!--start::Card toolbar-->
                        <div class="card-toolbar flex-row-fluid justify-content-end gap-5">
                            <div class="w-100 mw-175px">
                                <x-admin.form-elements.user-select placeholder="Tüm Kullanıcılar"
                                    customClass="form-select-sm" customAttr="comments-data-table-filter=user_id"
                                    :allowClear="true" />
                            </div>
                        </div>
                        <!--end::Card toolbar-->
                    </div>
                    <!--end::Card header-->

                    <!--begin::Card body-->
                    <div class="card-body pt-0 pb-5">
                        <x-admin.data-table tableId="commentsDataTable">
                            <x-slot name="header">
                                <th>Kullanıcı Adı</th>
                                <th>Yorum</th>
                                <th>Beğeni</th>
                                <th>Beğenilmeyen</th>
                                <th>Cevap</th>
                                <th>Tarih</th>
                                <th>İşlemler</th>
                            </x-slot>
                        </x-admin.data-table>
                    </div>
                    <!--end::Card body-->
                </div>
                <!--end::Card-->
            </div>
            <!--end:::Tab pane-->
            <!--begin:::Tab pane-->
            <div class="tab-pane fade" id="kt_report_problems_tab" role="tabpanel">
                <!--begin::Card-->
                <div class="card pt-4 mb-6 mb-xl-9">
                    <!--begin::Card header-->
                    <div class="card-header border-0">
                        <!--begin::Card title-->
                        <div class="card-title">
                            <x-admin.form-elements.search-input attr="report-problems-data-table-action=search"
                                class="form-control-sm" />
                        </div>
                        <!--end::Card title-->
                        <!--start::Card toolbar-->
                        <div class="card-toolbar flex-row-fluid justify-content-end gap-5">
                            <x-admin.filter-menu dropdownId="reportProblemsFilterMenu" buttonText="Filtrele" :buttonSm="true">
                                <div class="row gap-5">
                                    <div class="col-xl-12">
                                        <label class="form-label fs-7">Kategori:</label>
                                        <div>
                                            <x-admin.form-elements.report-problem-category-select
                                                placeholder="Tüm Kategoriler" customClass="form-select-sm"
                                                dropdownParent="#reportProblemsFilterMenu"
                                                customAttr="report-problems-data-table-filter=report_problem_category_id"
                                                :allowClear="true" :hideSearch="true" />
                                        </div>
                                    </div>
                                    <div class="col-xl-12">
                                        <label class="form-label fs-7">Durum:</label>
                                        <div>
                                            <x-admin.form-elements.report-problem-status-select
                                                placeholder="Tüm Durumlar" customClass="form-select-sm"
                                                dropdownParent="#reportProblemsFilterMenu"
                                                customAttr="report-problems-data-table-filter=status_id"
                                                :allowClear="true" :hideSearch="true" />
                                        </div>
                                    </div>
                                    <div class="col-xl-12">
                                        <label class="form-label fs-7">Kullanıcı:</label>
                                        <div>
                                            <x-admin.form-elements.user-select placeholder="Tüm Kullanıcılar"
                                                customClass="form-select-sm"
                                                dropdownParent="#reportProblemsFilterMenu"
                                                customAttr="report-problems-data-table-filter=user_id"
                                                :allowClear="true" />
                                        </div>
                                    </div>
                                </div>
                            </x-admin.filter-menu>
                        </div>
                        <!--end::Card toolbar-->
                    </div>
                    <!--end::Card header-->
                    <!--begin::Card body-->
                    <div class="card-body pt-0 pb-5">
                        <x-admin.data-table tableId="reportProblemsDataTable">
                            <x-slot name="header">
                                <th>Kullanıcı</th>
                                <th>Durum</th>
                                <th>Kategori</th>
                                <th>Şikayet Mesajı</th>
                                <th>İşlemler</th>
                            </x-slot>
                        </x-admin.data-table>
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
    $(document).ready(function() {
        let searchTimeout;
        const setLoading = (status, element) => {
            if (!element) {
                element = $('[data-content-area]')
            }
            if (status) {
                element?.addClass('loading');
                element?.find('[data-loading]').show();
            } else {
                element?.removeClass('loading');
                element?.find('[data-loading]').hide();
            }
        };

        /* start::Like-Comment Chart */
        const likeCommentContentArea = $('[data-content-area="like-comment"]');

        let likeCommentChart = {
            self: null,
            rendered: false
        };
        const likeCommentChartInit = (series, categories) => {
            const element = document.getElementById("likeCommentChart");

            if (!element) {
                return;
            }

            let height = parseInt(KTUtil.css(element, 'height'));
            let labelColor = KTUtil.getCssVariableValue('--bs-gray-500');
            let borderColor = KTUtil.getCssVariableValue('--bs-gray-200');
            let baseColor = KTUtil.getCssVariableValue('--bs-info');
            let lightColor = KTUtil.getCssVariableValue('--bs-info-light');


            let options = {
                series: series,
                chart: {
                    fontFamily: 'inherit',
                    type: 'area',
                    height: 350,
                    toolbar: {
                        show: true,
                        tools: {
                            download: false,
                            selection: false,
                            zoom: true,
                            zoomin: true,
                            zoomout: true,
                            pan: false,
                            reset: true,
                        }
                    },
                    zoom: {
                        enabled: true,
                    },
                },
                plotOptions: {

                },
                legend: {
                    show: false
                },
                dataLabels: {
                    enabled: false
                },
                fill: {
                    type: 'solid',
                    opacity: 1
                },
                stroke: {
                    curve: 'smooth',
                    show: true,
                    width: 3,
                    colors: ["#ff3366 ", "#6c757d"]
                },
                xaxis: {
                    categories: categories,
                    axisBorder: {
                        show: false,
                    },
                    axisTicks: {
                        show: false
                    },
                    labels: {
                        style: {
                            colors: labelColor,
                            fontSize: '12px'
                        }
                    },
                    crosshairs: {
                        position: 'front',
                        stroke: {
                            color: baseColor,
                            width: 1,
                            dashArray: 3
                        }
                    },
                    tooltip: {
                        enabled: true,
                        formatter: undefined,
                        offsetY: 0,
                        style: {
                            fontSize: '12px'
                        }
                    }
                },
                yaxis: {
                    labels: {
                        style: {
                            colors: labelColor,
                            fontSize: '12px'
                        },
                        formatter: function(val) {
                            return formatNumber(val);
                        }
                    }
                },
                states: {
                    normal: {
                        filter: {
                            type: 'none',
                            value: 0
                        }
                    },
                    hover: {
                        filter: {
                            type: 'none',
                            value: 0
                        }
                    },
                    active: {
                        allowMultipleDataPointsSelection: false,
                        filter: {
                            type: 'none',
                            value: 0
                        }
                    }
                },
                tooltip: {
                    style: {
                        fontSize: '12px'
                    },
                    y: {
                        formatter: function(val) {
                            return formatNumber(val)
                        }
                    }
                },
                colors: [lightColor],
                grid: {
                    borderColor: borderColor,
                    strokeDashArray: 4,
                    yaxis: {
                        lines: {
                            show: true
                        }
                    }
                },
                markers: {
                    strokeColor: baseColor,
                    strokeWidth: 3
                }
            };

            likeCommentChart.self = new ApexCharts(element, options);
            likeCommentChart.self.render();
            likeCommentChart.rendered = true;
        }
        const fetchLikeCommentChartDataAndRender = () => {
            $.ajax({
                url: "{{ route('admin.videos.get-like-comment-chart-data', ['id' => $video->id]) }}",
                method: 'GET',
                data: {
                    //
                },
                beforeSend: function() {
                    setLoading(true, likeCommentContentArea);
                },
                success: function(res) {
                    likeCommentChartInit(res.series, res.categories);
                },
                error: function(xhr) {
                    swal.error({
                        message: xhr.responseJSON?.message ?? null
                    })
                },
                complete: function() {
                    setLoading(false, likeCommentContentArea);
                }
            });
        }
        fetchLikeCommentChartDataAndRender();
        /* end::Like-Comment Chart */


        /* start::Edit */
        $(document).on("submit", "#editForm", function(e) {
            e.preventDefault();

            const button = $(this).find("[type='submit']");
            $.ajax({
                type: 'POST',
                url: "{{ route('admin.videos.update', ['id' => $video->id]) }}",
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



        /* start::Views */
        let viewsDataTable = $("#viewsDataTable").DataTable({
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
            ],
            "processing": true,
            "serverSide": true,
            "ajax": {
                "url": "{{ route('admin.videos.views.data-table', ['id' => $video->id]) }}",
                "type": "POST",
                "data": function(d) {
                    d._token = "{{ csrf_token() }}"
                    d.user_id = $('[views-data-table-filter="user_id"]').val()
                },
            },
        }).on("draw", function() {
            KTMenu.createInstances();
        });
        $(document).on("keyup", "[views-data-table-action='search']", function() {
            clearTimeout(searchTimeout);
            const searchValue = $(this).val();
            searchTimeout = setTimeout(function() {
                viewsDataTable.search(searchValue).draw();
            }, 500);
        })
        $(document).on("change", "[views-data-table-filter]", function() {
            viewsDataTable.draw()
        })
        /* end::Views */

        /* start::Likes */
        let likesDataTable = $("#likesDataTable").DataTable({
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
            ],
            "processing": true,
            "serverSide": true,
            "ajax": {
                "url": "{{ route('admin.videos.likes.data-table', ['id' => $video->id]) }}",
                "type": "POST",
                "data": function(d) {
                    d._token = "{{ csrf_token() }}"
                    d.user_id = $('[likes-data-table-filter="user_id"]').val()
                },
            },
        }).on("draw", function() {
            KTMenu.createInstances();
        });
        $(document).on("keyup", "[likes-data-table-action='search']", function() {
            clearTimeout(searchTimeout);
            const searchValue = $(this).val();
            searchTimeout = setTimeout(function() {
                likesDataTable.search(searchValue).draw();
            }, 500);
        })
        $(document).on("change", "[likes-data-table-filter]", function() {
            likesDataTable.draw()
        })
        /* end::Likes */

        /* start::Comments */
        let commentsDataTable = $("#commentsDataTable").DataTable({
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
                },
            ],
            "processing": true,
            "serverSide": true,
            "ajax": {
                "url": "{{ route('admin.videos.comments.data-table', ['id' => $video->id]) }}",
                "type": "POST",
                "data": function(d) {
                    d._token = "{{ csrf_token() }}"
                    d.user_id = $('[comments-data-table-filter="user_id"]').val()
                },
            },
        }).on("draw", function() {
            KTMenu.createInstances();
        });
        $(document).on("keyup", "[comments-data-table-action='search']", function() {
            clearTimeout(searchTimeout);
            const searchValue = $(this).val();
            searchTimeout = setTimeout(function() {
                commentsDataTable.search(searchValue).draw();
            }, 500);
        })
        $(document).on("change", "[comments-data-table-filter]", function() {
            commentsDataTable.draw()
        })
        /* end::Comments */

        /* start::ReportProblems */
        let reportProblemsDataTable = $("#reportProblemsDataTable").DataTable({
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
                },
            ],
            "processing": true,
            "serverSide": true,
            "ajax": {
                "url": "{{ route('admin.videos.report-problems.data-table', ['id' => $video->id]) }}",
                "type": "POST",
                "data": function(d) {
                    d._token = "{{ csrf_token() }}"
                    d.user_id = $('[report-problems-data-table-filter="user_id"]').val()
                },
            },
        }).on("draw", function() {
            KTMenu.createInstances();
        });
        $(document).on("keyup", "[report-problems-data-table-action='search']", function() {
            clearTimeout(searchTimeout);
            const searchValue = $(this).val();
            searchTimeout = setTimeout(function() {
                reportProblemsDataTable.search(searchValue).draw();
            }, 500);
        })
        $(document).on("change", "[report-problems-data-table-filter]", function() {
            reportProblemsDataTable.draw()
        })
        /* end::ReportProblems */
    })
</script>
@endsection
