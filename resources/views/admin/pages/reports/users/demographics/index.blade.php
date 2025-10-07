@extends('admin.template')
@section('title', 'Kullanıcı Demografik')
@section('breadcrumb')
    <x-admin.breadcrumb data="Kullanıcı Demografik" />
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
            <!--begin::Nav-->
            <ul class="nav nav-pills nav-pills-custom flex-center mb-5 gap-2">
                <!--begin::Item-->
                <li class="nav-item">
                    <!--begin::Link-->
                    <div class="nav-link d-flex justify-content-between flex-column flex-center overflow-hidden cursor-pointer p-5 active"
                        data-main-category="all_users" data-bs-toggle="pill">
                        <!--begin::Subtitle-->
                        <span class="nav-text text-gray-700 fw-bold fs-6 lh-1">Tüm Kullanıcılar</span>
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
                        data-main-category="sport_videos" data-bs-toggle="pill">
                        <!--begin::Subtitle-->
                        <span class="nav-text text-gray-700 fw-bold fs-6 lh-1">En Çok Spor Videoları İzleyenler</span>
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
                        data-main-category="other_videos" data-bs-toggle="pill">
                        <!--begin::Subtitle-->
                        <span class="nav-text text-gray-700 fw-bold fs-6 lh-1">En Çok Dİğer Videoları İzleyenler</span>
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
                        data-main-category="all_videos" data-bs-toggle="pill">
                        <!--begin::Subtitle-->
                        <span class="nav-text text-gray-700 fw-bold fs-6 lh-1">Tüm Videoları İzleyenler</span>
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
        <div class="col-xl-6">
            <!--begin::Card-->
            <div class="card card-stretch">
                <div class="card-body position-relative loading" data-content-area="age-range">
                    <!-- begin::Header-->
                    <div data-loading style="display: none;">
                        <span class="spinner-border spinner-border-sm align-middle text-primary border-2"></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-5">
                        <h4>Yaş Dağılımı</h4>
                        <div>
                            <div style="width: 210px;">
                                <x-admin.form-elements.date-range-picker customClass="form-control-sm"
                                    customAttr="data-age-range-chart-filter=date_range" />
                            </div>
                        </div>
                    </div>
                    <!-- end::Header-->
                    <!-- begin::Body-->
                    <div>
                        <div id="ageRangeChart" class="h-300px"></div>
                    </div>
                    <!-- end::Body-->
                </div>
            </div>
            <!--end::Card-->
        </div>
        <div class="col-xl-6">
            <!--begin::Card-->
            <div class="card card-stretch">
                <div class="card-body position-relative loading" data-content-area="gender">
                    <!-- begin::Header-->
                    <div data-loading style="display: none;">
                        <span class="spinner-border spinner-border-sm align-middle text-primary border-2"></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-5">
                        <h4>Cinsiyet Dağılımı</h4>
                        <div>
                            <div style="width: 210px;">
                                <x-admin.form-elements.date-range-picker customClass="form-control-sm"
                                    customAttr="data-gender-chart-filter=date_range" />
                            </div>
                        </div>
                    </div>
                    <!-- end::Header-->
                    <!-- begin::Body-->
                    <div>
                        <div class="d-flex h-100 flex-wrap align-items-center mt-18">
                            <div style="width: 100%; max-width: 200px; height: 200px;">
                                <canvas id="genderChart"></canvas>
                            </div>
                            <div class="d-flex flex-column justify-content-center flex-row-fluid px-13">
                                <div class="d-flex fs-6 fw-semibold align-items-center mb-3">
                                    <div class="bullet me-3" style="background-color: #ff0083"></div>
                                    <div class="text-gray-500">Kadın</div>
                                    <div class="ms-auto fw-bold text-gray-700" data-gender-chart-value="female">0</div>
                                </div>
                                <div class="d-flex fs-6 fw-semibold align-items-center mb-3">
                                    <div class="bullet me-3" style="background-color: #00A3FF"></div>
                                    <div class="text-gray-500">Erkek</div>
                                    <div class="ms-auto fw-bold text-gray-700" data-gender-chart-value="male">0</div>
                                </div>
                                <div class="d-flex fs-6 fw-semibold align-items-center">
                                    <div class="bullet me-3" style="background-color: #E4E6EF"></div>
                                    <div class="text-gray-500">Diğer</div>
                                    <div class="ms-auto fw-bold text-gray-700" data-gender-chart-value="other">0</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- end::Body-->
                </div>
            </div>
            <!--end::Card-->
        </div>
        <div class="col-xl-12">
            <!--begin::Card-->
            <div class="card">
                <div class="card-body position-relative loading" data-content-area="map">
                    <!-- begin::Header-->
                    <div data-loading style="display: none;">
                        <span class="spinner-border spinner-border-sm align-middle text-primary border-2"></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-5">
                        <h4>Şehirlere Dağılım</h4>
                        <div class="d-flex align-items-center gap-3">
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle" style="width: 16px; height: 16px; background-color: #cccccc;">
                                </div>
                                <small class="text-muted">Kullanıcı yok</small>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle" style="width: 16px; height: 16px; background-color: #b9fbc0;">
                                </div>
                                <small class="text-muted">En Az Kullanıcı</small>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle" style="width: 16px; height: 16px; background-color: #70d96d;">
                                </div>
                                <small class="text-muted">Orta</small>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle" style="width: 16px; height: 16px; background-color: #2d922c;">
                                </div>
                                <small class="text-muted">En Çok Kullanıcı</small>
                            </div>
                            <div style="width: 210px;">
                                <x-admin.form-elements.date-range-picker customClass="form-control-sm"
                                    customAttr="data-map-chart-filter=date_range" />
                            </div>
                        </div>
                    </div>
                    <!-- end::Header-->
                    <!-- begin::Body-->
                    <div class="mt-18">
                        <div id="map" style="width: 100%; height: 500px;"></div>
                    </div>
                    <!-- end::Body-->
                </div>
            </div>
            <!--end::Card-->
        </div>
    </div>
@endsection
@section('scripts')
    <script src="https://cdn.amcharts.com/lib/5/index.js"></script>
    <script src="https://cdn.amcharts.com/lib/5/map.js"></script>
    <script src="https://cdn.amcharts.com/lib/5/geodata/turkeyLow.js"></script>
    <script src="https://cdn.amcharts.com/lib/5/themes/Animated.js"></script>
    <script>
        const ageRangeContentArea = $('[data-content-area="age-range"]'),
            genderContentArea = $('[data-content-area="gender"]'),
            mapContentArea = $('[data-content-area="map"]');

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

        //--- start::age range chart ---
        let ageRangeChart = {
            self: null,
            rendered: false
        };
        const ageRangeChartInit = (data, categories) => {
            var element = document.getElementById("ageRangeChart");

            if (!element) return;

            var height = parseInt(KTUtil.css(element, 'height'));
            var labelColor = KTUtil.getCssVariableValue('--bs-gray-500');
            var borderColor = KTUtil.getCssVariableValue('--bs-gray-200');
            var baseColor = KTUtil.getCssVariableValue('--bs-primary');
            var secondaryColor = KTUtil.getCssVariableValue('--bs-gray-300');

            var options = {
                series: data,
                chart: {
                    fontFamily: 'inherit',
                    type: 'bar',
                    height: height,
                    toolbar: {
                        show: false
                    }
                },
                plotOptions: {
                    bar: {
                        horizontal: false,
                        columnWidth: ['30%'],
                        borderRadius: [6]
                    },
                },
                legend: {
                    show: false
                },
                dataLabels: {
                    enabled: false
                },
                stroke: {
                    show: true,
                    width: 2,
                    colors: ['transparent']
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
                    }
                },
                yaxis: {
                    labels: {
                        style: {
                            colors: labelColor,
                            fontSize: '12px'
                        },
                        formatter: function(val) {
                            if (val === Infinity || val === -Infinity || isNaN(val)) {
                                return 1;
                            }
                            return val;
                        },
                    }
                },
                fill: {
                    opacity: 1
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
                            return val + " kişi"
                        }
                    }
                },
                colors: [baseColor],
                grid: {
                    borderColor: borderColor,
                    strokeDashArray: 4,
                    yaxis: {
                        lines: {
                            show: true
                        }
                    }
                }
            };

            ageRangeChart.self = new ApexCharts(element, options);
            ageRangeChart.rendered = true;
            ageRangeChart.self.render();
        }
        const ageRangeChartUpdate = (newData, newCategories) => {
            if (ageRangeChart.rendered && ageRangeChart.self) {
                ageRangeChart.self.updateOptions({
                    series: newData,
                    xaxis: {
                        categories: newCategories
                    }
                });
            }
        }
        const fetchAgeRangeChartDataAndRender = () => {
            let dateRangePicker = $('[data-age-range-chart-filter="date_range"]').data('daterangepicker');
            let startDate = dateRangePicker?.startDate.format('YYYY-MM-DD');
            let endDate = dateRangePicker?.endDate.format('YYYY-MM-DD');
            let mainCategoryValue = $('[data-main-category].active').data('main-category')


            $.ajax({
                url: "{{ route('admin.reports.users.demographics.get-age-range-chart-data') }}",
                method: 'GET',
                data: {
                    category: mainCategoryValue,
                    start_date: startDate,
                    end_date: endDate
                },
                beforeSend: function() {
                    setLoading(true, ageRangeContentArea);
                },
                success: function(res) {
                    if (ageRangeChart.rendered) {
                        ageRangeChartUpdate(res.series, res.categories);
                    } else {
                        ageRangeChartInit(res.series, res.categories);
                    }
                },
                error: function(xhr) {
                    swal.error({
                        message: xhr.responseJSON?.message ?? null
                    })
                },
                complete: function() {
                    setLoading(false, ageRangeContentArea);
                }
            });
        }
        //--- end::age range chart ---


        //--- start::gender chart ---
        let genderChart = false;
        const genderChartInit = (data, categories) => {
            var element = document.getElementById("genderChart");

            if (!element) {
                return;
            }

            var config = {
                type: 'doughnut',
                data: {
                    datasets: data,
                    labels: categories
                },
                options: {
                    chart: {
                        fontFamily: 'inherit'
                    },
                    borderWidth: 0,
                    cutout: '65%',
                    cutoutPercentage: 65,
                    responsive: true,
                    maintainAspectRatio: false,
                    title: {
                        display: false
                    },
                    animation: {
                        animateScale: true,
                        animateRotate: true
                    },
                    stroke: {
                        width: 0
                    },
                    tooltips: {
                        enabled: true,
                        intersect: false,
                        mode: 'nearest',
                        bodySpacing: 5,
                        yPadding: 10,
                        xPadding: 10,
                        caretPadding: 0,
                        displayColors: false,
                        backgroundColor: '#20D489',
                        titleFontColor: '#ffffff',
                        cornerRadius: 4,
                        footerSpacing: 0,
                        titleSpacing: 0
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                }
            };

            var ctx = element.getContext('2d');
            genderChart = new Chart(ctx, config);
        }
        const genderChartUpdate = (newData, newCategories) => {
            if (genderChart) {
                genderChart.data.datasets = newData;
                genderChart.data.labels = newCategories;
                genderChart.update();
            }
        }
        const fetchGenderChartDataAndRender = () => {
            let dateRangePicker = $('[data-gender-chart-filter="date_range"]').data('daterangepicker');
            let startDate = dateRangePicker?.startDate.format('YYYY-MM-DD');
            let endDate = dateRangePicker?.endDate.format('YYYY-MM-DD');
            let mainCategoryValue = $('[data-main-category].active').data('main-category');

            $.ajax({
                url: "{{ route('admin.reports.users.demographics.get-gender-chart-data') }}",
                method: 'GET',
                data: {
                    category: mainCategoryValue,
                    start_date: startDate,
                    end_date: endDate
                },
                beforeSend: function() {
                    setLoading(true, genderContentArea);
                },
                success: function(res) {
                    if (genderChart) {
                        genderChartUpdate(res.datasets, res.labels);
                    } else {
                        genderChartInit(res.datasets, res.labels);
                    }

                    $('[data-gender-chart-value="female"]').text(res?.datasets[0]?.data[0] ?? 0);
                    $('[data-gender-chart-value="male"]').text(res?.datasets[0]?.data[1] ?? 0);
                    $('[data-gender-chart-value="other"]').text(res?.datasets[0]?.data[2] ?? 0);
                },
                error: function(xhr) {
                    swal.error({
                        message: xhr.responseJSON?.message ?? null
                    })
                },
                complete: function() {
                    setLoading(false, genderContentArea);
                }
            });
        }
        //--- end::gender chart ---


        //--- start::map chart ---
        let polygonSeries = false;
        const mapInit = (newData) => {
            am5.ready(function() {
                // Root oluştur
                var root = am5.Root.new("map");
                root.setThemes([am5themes_Animated.new(root)]);

                // Harita chart'ı oluştur
                var chart = root.container.children.push(
                    am5map.MapChart.new(root, {
                        panX: "none",
                        panY: "none",
                        wheelY: "none",
                        projection: am5map.geoMercator(),
                    })
                );

                // Poligon (şehirler)
                polygonSeries = chart.series.push(
                    am5map.MapPolygonSeries.new(root, {
                        geoJSON: am5geodata_turkeyLow,
                        valueField: "value",
                        calculateAggregates: true,
                    })
                );

                polygonSeries.mapPolygons.template.setAll({
                    fill: am5.color("#dedede"), // 0 kullanıcılı şehirler
                });

                // Renk skalası
                var heatRule = polygonSeries.set("heatRules", [{
                    target: polygonSeries.mapPolygons.template,
                    dataField: "value",
                    min: am5.color("#b9fbc0"), // en az kullanıcı
                    max: am5.color("#2d922c"), // en çok kullanıcı
                    key: "fill",
                }]);

                polygonSeries.mapPolygons.template.adapters.add("tooltipText", function(text, target) {
                    let data = target.dataItem?.dataContext || {},
                        name = data.name || "Bilinmeyen",
                        value = data.value ?? 0,
                        formattedValue = new Intl.NumberFormat('tr-TR').format(value);

                    return `${name}: ${formattedValue} kullanıcı`;
                });

                // Her poligon (il) için tooltip
                polygonSeries.mapPolygons.template.setAll({
                    interactive: true
                });

                polygonSeries.mapPolygons.template.states.create("hover", {
                    fill: am5.color("#555")
                });

                polygonSeries.data.setAll(newData);
            });
        }
        const fetchMapDataAndRender = () => {
            let dateRangePicker = $('[data-map-chart-filter="date_range"]').data('daterangepicker');
            let startDate = dateRangePicker?.startDate.format('YYYY-MM-DD');
            let endDate = dateRangePicker?.endDate.format('YYYY-MM-DD');
            let mainCategoryValue = $('[data-main-category].active').data('main-category');

            $.ajax({
                url: "{{ route('admin.reports.users.demographics.get-map-data') }}",
                method: 'GET',
                data: {
                    category: mainCategoryValue,
                    start_date: startDate,
                    end_date: endDate
                },
                beforeSend: function() {
                    setLoading(true, mapContentArea);
                },
                success: function(res) {
                    if (polygonSeries) {
                        polygonSeries.data.setAll(res.data);
                    } else {
                        mapInit(res.data);
                    }
                },
                error: function(xhr) {
                    swal.error({
                        message: xhr.responseJSON?.message ?? null
                    })
                },
                complete: function() {
                    setLoading(false, mapContentArea);
                }
            });
        }
        //--- end::map chart ---


        const refreshAllPageData = () => {
            fetchAgeRangeChartDataAndRender();
            fetchGenderChartDataAndRender();
            fetchMapDataAndRender();
        }

        $(document).ready(function() {
            /* --- start::age range chart --- */
            let ageRangeFirstLoad = true; //date range picker yuzunden fetch 2 kere render olmasın diye eklendi.
            $(document).on("change", "[data-age-range-chart-filter]", function() {
                if (ageRangeFirstLoad) {
                    ageRangeFirstLoad = false;
                    return;
                }
                fetchAgeRangeChartDataAndRender();
            })
            /* --- end::age range chart --- */

            /* --- start::gender chart --- */
            let genderFirstLoad = true;
            $(document).on("change", "[data-gender-chart-filter]", function() {
                if (genderFirstLoad) {
                    genderFirstLoad = false;
                    return;
                }
                fetchGenderChartDataAndRender();
            })
            /* --- end::gender chart --- */

            /* --- start::map chart --- */
            let mapFirstLoad = true;
            $(document).on("change", "[data-map-chart-filter]", function() {
                if (mapFirstLoad) {
                    mapFirstLoad = false;
                    return;
                }
                fetchMapDataAndRender();
            })
            /* --- end::map chart --- */

            refreshAllPageData();

            $(document).on("click", "[data-main-category]", function() {
                refreshAllPageData();
            })
        })
    </script>
@endsection
