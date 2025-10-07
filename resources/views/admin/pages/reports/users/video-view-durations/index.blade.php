@extends('admin.template')
@section('title', 'Video İzleme Süreleri')
@section('breadcrumb')
    <x-admin.breadcrumb data="Video İzleme Süreleri" />
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
    <div class="row g-5">
        <div class="col-12">
            <div class="col-12">
                <!--begin::Nav-->
                <ul class="nav nav-pills nav-pills-custom flex-center mb-5 gap-2">
                    <!--begin::Item-->
                    <li class="nav-item">
                        <!--begin::Link-->
                        <div class="nav-link d-flex justify-content-between flex-column flex-center overflow-hidden cursor-pointer p-5 active"
                            data-main-category="all" data-bs-toggle="pill">
                            <!--begin::Subtitle-->
                            <span class="nav-text text-gray-700 fw-bold fs-6 lh-1">Tüm Videolar</span>
                            <!--end::Subtitle-->
                            <!--begin::Bullet-->
                            <span class="bullet-custom position-absolute bottom-0 w-100 h-4px bg-primary"></span>
                            <!--end::Bullet-->
                        </div>
                        <!--end::Link-->
                    </li>
                    <!--end::Item-->
                    <!--begin::Item-->
                    <li class="nav-item">
                        <!--begin::Link-->
                        <div class="nav-link d-flex justify-content-between flex-column flex-center overflow-hidden cursor-pointer p-5"
                            data-main-category="sport" data-bs-toggle="pill">
                            <!--begin::Subtitle-->
                            <span class="nav-text text-gray-700 fw-bold fs-6 lh-1">Spor Videoları</span>
                            <!--end::Subtitle-->
                            <!--begin::Bullet-->
                            <span class="bullet-custom position-absolute bottom-0 w-100 h-4px bg-primary"></span>
                            <!--end::Bullet-->
                        </div>
                        <!--end::Link-->
                    </li>
                    <!--end::Item-->
                    <!--begin::Item-->
                    <li class="nav-item">
                        <!--begin::Link-->
                        <div class="nav-link d-flex justify-content-between flex-column flex-center overflow-hidden cursor-pointer p-5"
                            data-main-category="other" data-bs-toggle="pill">
                            <!--begin::Subtitle-->
                            <span class="nav-text text-gray-700 fw-bold fs-6 lh-1">Diğer Videolar</span>
                            <!--end::Subtitle-->
                            <!--begin::Bullet-->
                            <span class="bullet-custom position-absolute bottom-0 w-100 h-4px bg-primary"></span>
                            <!--end::Bullet-->
                        </div>
                        <!--end::Link-->
                    </li>
                    <!--end::Item-->
                </ul>
                <!--end::Nav-->
                <div class="separator my-5"></div>
            </div>
        </div>
        <div class="col-xl-6">
            <!--begin::Card-->
            <div class="card card-stretch">
                <div class="card-body">
                    <div>
                        <h4 class="mb-0">Toplam İzlenme Süresi</h4>
                        <div class="text-muted fs-7">Tüm zamanlar</div>
                    </div>
                    <div class="position-relative loading" data-content-area="stats">
                        <div data-loading style="display: none;">
                            <span class="spinner-border spinner-border-sm align-middle text-primary border-2"></span>
                        </div>
                        <span class="fs-2hx fw-bold mb-3" data-total-view-duration>0</span>
                    </div>
                </div>
            </div>
            <!--end::Card-->
        </div>
        <div class="col-xl-6">
            <!--begin::Card-->
            <div class="card card-stretch">
                <div class="card-body">
                    <div>
                        <h4 class="mb-0">Benzersiz İzleyici Sayısı</h4>
                        <div class="text-muted fs-7">Tüm zamanlar</div>
                    </div>
                    <div>
                        <div class="position-relative loading" data-content-area="stats">
                            <div data-loading style="display: none;">
                                <span class="spinner-border spinner-border-sm align-middle text-primary border-2"></span>
                            </div>
                            <span class="fs-2hx fw-bold mb-3" data-unique-viewer-count>0</span>
                        </div>
                    </div>
                </div>
            </div>
            <!--end::Card-->
        </div>
        <div class="col-xl-8">
            <!--begin::Card-->
            <div class="card card-stretch">
                <div class="card-body position-relative loading" data-content-area="chart">
                    <!-- begin::Header-->
                    <div data-loading style="display: none;">
                        <span class="spinner-border spinner-border-sm align-middle text-primary border-2"></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-5">
                        <h4>Grafiksel Gösterim</h4>
                        <div>
                            <a class="btn btn-sm btn-color-muted btn-active btn-active-primary active px-4 me-1"
                                id="kt_charts_widget_2_year_btn">Bu Yıl</a>
                            <div class="d-none" style="width: 210px">
                                <x-admin.form-elements.date-range-picker customAttr="data-chart-filter=date_range" />
                            </div>
                        </div>
                    </div>
                    <!-- end::Header-->
                    <!-- begin::Body-->
                    <div>
                        <div id="chart" class="h-100"></div>
                    </div>
                    <!-- end::Body-->
                </div>
            </div>
            <!--end::Card-->
        </div>
        <div class="col-xl-4">
            <!--begin::Card-->
            <div class="card card-stretch">
                <!--begin::Body-->
                <div class="card-body position-relative loading" data-content-area="top-viewer-users">
                    <!-- begin::Header-->
                    <div data-loading>
                        <span class="spinner-border spinner-border-sm align-middle text-primary border-2"></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-5">
                        <h4>En Çok İzleyenler</h4>
                        <div>
                            
                        </div>
                    </div>
                    <!-- end::Header-->
                    <!-- begin::Body-->
                    <div>
                        <div data-items></div>
                        <div class="d-none" data-empty-item>
                            <!--begin::Item-->
                            <div class="d-flex align-items-center">
                                <!--begin::Avatar-->
                                <div class="symbol symbol-50px me-5" data-symbol></div>
                                <!--end::Avatar-->
                                <!--begin::Text-->
                                <div class="w-100 d-flex justify-content-between align-items-center gap-1">
                                    <div>
                                        <a target="_blank" href="javascript:void(0);"
                                            class="text-gray-900 fw-bold text-hover-primary fs-6" data-full-name></a>
                                        <span class="text-muted d-block fw-bold" data-nickname></span>
                                    </div>
                                    <div><span class="badge badge-success" data-total-duration>0</span></div>
                                </div>
                                <!--end::Text-->
                            </div>
                            <!--end::Item-->
                            <div class="separator separator-dashed my-3"></div>
                        </div>
                    </div>
                    <!-- end::Body-->
                </div>
                <!--end::Body-->
            </div>
            <!--end::Card-->
        </div>
    </div>
@endsection
@section('scripts')
    <script>
        const chartContentArea = $('[data-content-area="chart"]'),
            statsContentArea = $('[data-content-area="stats"]'),
            topViewerUsersContentArea = $('[data-content-area="top-viewer-users"]');

        const setLoading = (status, element) => {
            if (!element) {
                element = $('[data-card]')
            }
            if (status) {
                element?.addClass('loading');
                element?.find('[data-loading]').show();
            } else {
                element?.removeClass('loading');
                element?.find('[data-loading]').hide();
            }
        };

        var chart = {
            self: null,
            rendered: false
        };
        const chartInit = (data, categories) => {
            var element = document.getElementById("chart");

            if (!element) return;

            var labelColor = KTUtil.getCssVariableValue('--bs-gray-500');
            var borderColor = KTUtil.getCssVariableValue('--bs-gray-200');
            var baseColor = KTUtil.getCssVariableValue('--bs-info');
            var lightColor = KTUtil.getCssVariableValue('--bs-info-light');

            var options = {
                series: data,
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
                    colors: [baseColor]
                },
                xaxis: {
                    categories: categories,
                    labels: {
                        style: {
                            colors: labelColor,
                            fontSize: '12px'
                        }
                    }
                },
                yaxis: {
                    labels: {
                        style: {
                            colors: labelColor,
                            fontSize: '12px'
                        }
                    }
                },
                tooltip: {
                    style: {
                        fontSize: '12px'
                    },
                    y: {
                        formatter: function(val) {
                            return formatToHourMinute(val, 1);
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

            chart.self = new ApexCharts(element, options);
            chart.rendered = true;
            chart.self.render();
        }
        const chartUpdate = (newData, newCategories) => {
            if (chart.rendered && chart.self) {
                chart.self.updateOptions({
                    series: newData,
                    xaxis: {
                        categories: newCategories
                    }
                });
            }
        }
        const fetchChartDataAndRender = () => {
            let mainCategoryValue = $('[data-main-category].active').data('main-category');
            let dateRangePicker = $('[data-chart-filter="date_range"]').data('daterangepicker');
            let startDate = dateRangePicker?.startDate.format('YYYY-MM-DD');
            let endDate = dateRangePicker?.endDate.format('YYYY-MM-DD');

            $.ajax({
                url: "{{ route('admin.reports.users.video-view-durations.chart-data') }}",
                method: 'GET',
                data: {
                    category: mainCategoryValue,
                    start_date: startDate,
                    end_date: endDate
                },
                beforeSend: function() {
                    setLoading(true, chartContentArea);
                },
                success: function(res) {
                    if (chart.rendered) {
                        chartUpdate(res.series, res.categories);
                    } else {
                        chartInit(res.series, res.categories);
                    }
                },
                error: function(xhr) {
                    swal.error({
                        message: xhr.responseJSON?.message ?? null
                    })
                },
                complete: function() {
                    setLoading(false, chartContentArea);
                }
            });
        }


        const fetchStats = () => {
            let mainCategoryValue = $('[data-main-category].active').data('main-category');

            $.ajax({
                url: "{{ route('admin.reports.users.video-view-durations.get-stats') }}",
                method: 'GET',
                data: {
                    category: mainCategoryValue,
                },
                beforeSend: function() {
                    setLoading(true, statsContentArea);
                },
                success: function(res) {
                    $('[data-total-view-duration]').text(res.total_view_duration);
                    $('[data-unique-viewer-count]').text(res.unique_viewer_count + ' Kişi');
                },
                error: function() {
                    swal.error({
                        message: xhr.responseJSON?.message ?? null
                    })
                },
                complete: function() {
                    setLoading(false, statsContentArea);
                }
            });
        }


        const fetchTopViewerUsers = () => {
            let mainCategoryValue = $('[data-main-category].active').data('main-category');
            $.ajax({
                url: "{{ route('admin.reports.users.video-view-durations.get-top-viewer-users') }}",
                method: 'GET',
                data: {
                    category: mainCategoryValue,
                },
                beforeSend: function() {
                    setLoading(true, topViewerUsersContentArea);
                },
                success: function(res) {
                    $("[data-items]").empty();
                    res.items.forEach(item => {
                        let itemElement = $("[data-empty-item]");

                        itemElement.find("[data-symbol]").html(
                            `<span class='symbol-label bg-secondary text-inverse-secondary fw-bold fs-4'>${item.user.nickname[0].toUpperCase()}</span>`
                        );
                        itemElement.find("[data-full-name]").text(item.user.full_name);
                        itemElement.find("[data-full-name]").attr("href", item.user.redirect_url);
                        itemElement.find("[data-nickname]").text(item.user.nickname);
                        itemElement.find("[data-image]").attr("src", item.user.image);
                        itemElement.find("[data-total-duration]").text(item.total_duration);
                        $("[data-items]").append(itemElement.html());
                    });
                },
                error: function() {
                    swal.error({
                        message: xhr.responseJSON?.message ?? null
                    })
                },
                complete: function() {
                    setLoading(false, topViewerUsersContentArea);
                }
            });
        }


        const refreshAllPageData = () => {
            fetchStats();
            fetchChartDataAndRender();
            fetchTopViewerUsers();
        }

        $(document).ready(function() {

            $(document).on("change", "[data-chart-filter]", function() {
                fetchChartDataAndRender();
            })

            $(document).on("click", "[data-main-category]", function() {
                refreshAllPageData();
            })

            refreshAllPageData();
        })
    </script>
@endsection
