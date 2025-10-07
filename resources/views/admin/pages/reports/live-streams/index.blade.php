@extends('admin.template')
@section('title', 'Canlı Yayın Raporları')
@section('breadcrumb')
<x-admin.breadcrumb data="Canlı Yayın Raporları" />
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
    <div class="col-xl-6">
        <!--begin::Card-->
        <div class="card card-stretch">
            <div class="card-body position-relative loading" data-content-area="duration">
                <!-- begin::Header-->
                <div data-loading style="display: none;">
                    <span class="spinner-border spinner-border-sm align-middle text-primary border-2"></span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-5">
                    <h4>Günlük Yayın Süreleri</h4>
                    <div>
                        <div style="width: 130px;">
                            <x-admin.form-elements.date-input customClass="form-control-sm" :value="$now->copy()->subDays(6)->startOfDay()"
                                customAttr="data-duration-chart-filter=start_date" />
                        </div>
                    </div>
                </div>
                <!-- end::Header-->
                <!-- begin::Body-->
                <div>
                    <div id="durationChart" class="h-300px"></div>
                </div>
                <!-- end::Body-->
            </div>
        </div>
        <!--end::Card-->
    </div>
    <div class="col-xl-6">
        <!--begin::Card-->
        <div class="card card-stretch">
            <div class="card-body position-relative loading" data-content-area="hour">
                <!-- begin::Header-->
                <div data-loading style="display: none;">
                    <span class="spinner-border spinner-border-sm align-middle text-primary border-2"></span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-5">
                    <h4>Saat Aralıkları</h4>
                    <div>
                        <div style="width: 210px;">
                            <x-admin.form-elements.date-range-picker customClass="form-control-sm"
                                customAttr="data-hour-chart-filter=date_range" />
                        </div>
                    </div>
                </div>
                <!-- end::Header-->
                <!-- begin::Body-->
                <div>
                    <div id="hourChart" class="h-300px"></div>
                </div>
                <!-- end::Body-->
            </div>
        </div>
        <!--end::Card-->
    </div>
    <div class="col-xl-6">
        <!--begin::Card-->
        <div class="card card-stretch">
            <div class="card-body position-relative loading" data-content-area="open-stream-by-team">
                <!-- begin::Header-->
                <div data-loading style="display: none;">
                    <span class="spinner-border spinner-border-sm align-middle text-primary border-2"></span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-5">
                    <div>
                        <h4 class="mb-0">Takım Dağılımı</h4>
                        <div class="text-gray-500 fw-semibold fs-7">Toplam yayın açma süresi</div>
                    </div>
                    <div>
                        <div style="width: 210px;">
                            <x-admin.form-elements.date-range-picker customClass="form-control-sm"
                                customAttr="data-open-stream-by-team-chart-filter=date_range" />
                        </div>
                    </div>
                </div>
                <!-- end::Header-->
                <!-- begin::Body-->
                <div>
                    <div class="d-flex h-100 flex-wrap align-items-center justify-content-around mt-18">
                        <div style="width: 100%; max-width: 200px; height: 200px;">
                            <canvas id="openStreamByTeamChart"></canvas>
                        </div>
                        <div data-info></div>
                        <div data-empty-info style="display: none;">
                            <div class="d-flex fs-6 fw-semibold align-items-center mb-3">
                                <div class="bullet me-3 color"></div>
                                <div class="text-gray-500 label me-3"></div>
                                <div class="ms-auto fw-bold text-gray-700 fs-7 value">0</div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- end::Body-->
            </div>
        </div>
        <!--end::Card-->
    </div>
    <div class="col-xl-6">
        <!--begin::Card-->
        <div class="card card-stretch">
            <div class="card-body position-relative loading" data-content-area="watchers-by-team">
                <!-- begin::Header-->
                <div data-loading style="display: none;">
                    <span class="spinner-border spinner-border-sm align-middle text-primary border-2"></span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-5">
                    <div>
                        <h4 class="mb-0">Takım Dağılımı</h4>
                        <div class="text-gray-500 fw-semibold fs-7">Toplam yayın izleme süresi</div>
                    </div>
                    <div>
                        <div style="width: 210px;">
                            <x-admin.form-elements.date-range-picker customClass="form-control-sm"
                                customAttr="data-watchers-by-team-chart-filter=date_range" />
                        </div>
                    </div>
                </div>
                <!-- end::Header-->
                <!-- begin::Body-->
                <div>
                    <div class="d-flex h-100 flex-wrap align-items-center justify-content-around mt-18">
                        <div style="width: 100%; max-width: 200px; height: 200px;">
                            <canvas id="watchersByTeamChart"></canvas>
                        </div>
                        <div data-info></div>
                        <div data-empty-info style="display: none;">
                            <div class="d-flex fs-6 fw-semibold align-items-center mb-3">
                                <div class="bullet me-3 color"></div>
                                <div class="text-gray-500 label me-3"></div>
                                <div class="ms-auto fw-bold text-gray-700 fs-7 value">0</div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- end::Body-->
            </div>
        </div>
        <!--end::Card-->
    </div>
    <div class="col-xl-6">
        <!--begin::Card-->
        <div class="card card-stretch">
            <div class="card-body position-relative loading" data-content-area="gifts">
                <!-- begin::Header-->
                <div data-loading style="display: none;">
                    <span class="spinner-border spinner-border-sm align-middle text-primary border-2"></span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-5">
                    <h4>Hediye Gönderim Grafiği</h4>
                    <div>
                        <div style="width: 130px;">
                            <x-admin.form-elements.date-input customClass="form-control-sm" :value="$now->copy()->subDays(6)->startOfDay()"
                                customAttr="data-gifts-chart-filter=start_date" />
                        </div>
                    </div>
                </div>
                <!-- end::Header-->
                <!-- begin::Body-->
                <div>
                    <div id="gifts-chart" class="h-100"></div>
                </div>
                <!-- end::Body-->
            </div>
        </div>
        <!--end::Card-->
    </div>
    <div class="col-xl-6">
        <!--begin::Card-->
        <div class="card card-stretch">
            <div class="card-body position-relative loading" data-content-area="watchers">
                <!-- begin::Header-->
                <div data-loading style="display: none;">
                    <span class="spinner-border spinner-border-sm align-middle text-primary border-2"></span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-5">
                    <h4>İzleyici Sayısı Grafiği</h4>
                    <div>
                        <div style="width: 130px;">
                            <x-admin.form-elements.date-input customClass="form-control-sm" :value="$now->copy()->subDays(6)->startOfDay()"
                                customAttr="data-watchers-chart-filter=start_date" />
                        </div>
                    </div>
                </div>
                <!-- end::Header-->
                <!-- begin::Body-->
                <div>
                    <div id="watchers-chart" class="h-100"></div>
                </div>
                <!-- end::Body-->
            </div>
        </div>
        <!--end::Card-->
    </div>
    <div class="col-xl-6">
        <!--begin::Card-->
        <div class="card card-stretch">
            <!--begin::Body-->
            <div class="card-body position-relative loading" data-content-area="top-gifts">
                <!-- begin::Header-->
                <div data-loading>
                    <span class="spinner-border spinner-border-sm align-middle text-primary border-2"></span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-5">
                    <h4>En Çok Kazanan 5 Yayın</h4>
                    <div>
                        <div style="width: 210px;">
                            <x-admin.form-elements.date-range-picker customClass="form-control-sm"
                                customAttr="data-top-gifts-chart-filter=date_range" />
                        </div>
                    </div>
                </div>
                <!-- end::Header-->
                <!-- begin::Body-->
                <div>
                    <div data-items></div>
                    <div class="d-none" data-empty-item>
                        <!--begin::Item-->
                        <div class="d-flex align-items-center">
                            <!--begin::Text-->
                            <div class="w-100 d-flex justify-content-between align-items-center gap-1">
                                <div>
                                    <a target="_blank" href="javascript:void(0);"
                                        class="text-gray-900 fw-bold text-hover-primary fs-6" data-full-name></a>
                                    <span class="text-muted d-block fw-bold" data-label></span>
                                </div>
                                <div><span class="badge badge-success"><span class="me-1" data-total-coins>0</span> coin</span></div>
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
<script src="https://cdn.amcharts.com/lib/5/index.js"></script>
<script src="https://cdn.amcharts.com/lib/5/themes/Animated.js"></script>
<script>
    const durationContentArea = $('[data-content-area="duration"]'),
        hourContentArea = $('[data-content-area="hour"]'),
        openStreamByTeamContentArea = $('[data-content-area="open-stream-by-team"]'),
        watchersByTeamContentArea = $('[data-content-area="watchers-by-team"]'),
        topGiftsContentArea = $('[data-content-area="top-gifts"]'),
        giftsContentArea = $('[data-content-area="gifts"]'),
        watchersContentArea = $('[data-content-area="watchers"]');

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

    //--- start::duration chart ---
    let durationChart = {
        self: null,
        rendered: false
    };
    const durationChartInit = (data, categories) => {
        var element = document.getElementById("durationChart");

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
                        const minutes = (val / 60).toFixed(1);
                        return `${minutes} dk`;
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
                        return formatToHourMinute(val);
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

        durationChart.self = new ApexCharts(element, options);
        durationChart.rendered = true;
        durationChart.self.render();
    }
    const durationChartUpdate = (newData, newCategories) => {
        if (durationChart.rendered && durationChart.self) {
            durationChart.self.updateOptions({
                series: newData,
                xaxis: {
                    categories: newCategories
                }
            });
        }
    }
    const fetchDurationChartDataAndRender = () => {
        let startDate = $('[data-duration-chart-filter="start_date"]').val();

        $.ajax({
            url: "{{ route('admin.reports.live-streams.get-duration-chart-data') }}",
            method: 'GET',
            data: {
                start_date: startDate
            },
            beforeSend: function() {
                setLoading(true, durationContentArea);
            },
            success: function(res) {
                if (durationChart.rendered) {
                    durationChartUpdate(res.series, res.categories);
                } else {
                    durationChartInit(res.series, res.categories);
                }
            },
            error: function(xhr) {
                swal.error({
                    message: xhr.responseJSON?.message ?? null
                })
            },
            complete: function() {
                setLoading(false, durationContentArea);
            }
        });
    }
    //--- end::duration chart ---

    //--- start::hour chart ---
    let hourChart = {
        self: null,
        rendered: false
    };
    const hourChartInit = (data, categories) => {
        var element = document.getElementById("hourChart");

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
                        const minutes = (val / 60).toFixed(1);
                        return `${minutes} dk`;
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
                        return formatToHourMinute(val);
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

        hourChart.self = new ApexCharts(element, options);
        hourChart.rendered = true;
        hourChart.self.render();
    }
    const hourChartUpdate = (newData, newCategories) => {
        if (hourChart.rendered && hourChart.self) {
            hourChart.self.updateOptions({
                series: newData,
                xaxis: {
                    categories: newCategories
                }
            });
        }
    }
    const fetchHourChartDataAndRender = () => {
        let dateRangePicker = $('[data-hour-chart-filter="date_range"]').data('daterangepicker');
        let startDate = dateRangePicker?.startDate.format('YYYY-MM-DD');
        let endDate = dateRangePicker?.endDate.format('YYYY-MM-DD');

        $.ajax({
            url: "{{ route('admin.reports.live-streams.get-hour-chart-data') }}",
            method: 'GET',
            data: {
                start_date: startDate,
                end_date: endDate
            },
            beforeSend: function() {
                setLoading(true, hourContentArea);
            },
            success: function(res) {
                if (hourChart.rendered) {
                    hourChartUpdate(res.series, res.categories);
                } else {
                    hourChartInit(res.series, res.categories);
                }
            },
            error: function(xhr) {
                swal.error({
                    message: xhr.responseJSON?.message ?? null
                })
            },
            complete: function() {
                setLoading(false, hourContentArea);
            }
        });
    }
    //--- end::hour chart ---

    //--- start::open stream by team chart ---
    let openStreamByTeamChart = false;
    const openStreamByTeamChartInit = (data, categories) => {
        var element = document.getElementById("openStreamByTeamChart");

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
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed;

                                const formatted = formatToHourMinute(value);
                                return `${label}: ${formatted}`;
                            }
                        }
                    }
                },
            }
        };

        var ctx = element.getContext('2d');
        openStreamByTeamChart = new Chart(ctx, config);
    }
    const openStreamByTeamChartUpdate = (newData, newCategories) => {
        if (openStreamByTeamChart) {
            openStreamByTeamChart.data.datasets = newData;
            openStreamByTeamChart.data.labels = newCategories;
            openStreamByTeamChart.update();
        }
    }
    const fetchOpenStreamByTeamChartDataAndRender = () => {
        let dateRangePicker = $('[data-open-stream-by-team-chart-filter="date_range"]').data('daterangepicker');
        let startDate = dateRangePicker?.startDate.format('YYYY-MM-DD');
        let endDate = dateRangePicker?.endDate.format('YYYY-MM-DD');

        $.ajax({
            url: "{{ route('admin.reports.live-streams.get-open-stream-by-team-chart-data') }}",
            method: 'GET',
            data: {
                start_date: startDate,
                end_date: endDate
            },
            beforeSend: function() {
                setLoading(true, openStreamByTeamContentArea);
            },
            success: function(res) {
                if (openStreamByTeamChart) {
                    openStreamByTeamChartUpdate(res.datasets, res.labels);
                } else {
                    openStreamByTeamChartInit(res.datasets, res.labels);
                }

                const emptyInfo = openStreamByTeamContentArea.find('[data-empty-info]');
                const info = openStreamByTeamContentArea.find('[data-info]');


                info.html('');
                res.labels.forEach((label, index) => {
                    const value = res.datasets[0].data[index],
                        bgColor = res.datasets[0].backgroundColor[index];

                    if (value >= 0) {
                        emptyInfo.find('.color').css('background-color', bgColor);
                        emptyInfo.find('.value').text(formatToHourMinute(value));
                        emptyInfo.find('.label').text(label);

                        info.append(emptyInfo.html());
                    }
                })

            },
            error: function(xhr) {
                swal.error({
                    message: xhr.responseJSON?.message ?? null
                })
            },
            complete: function() {
                setLoading(false, openStreamByTeamContentArea);
            }
        });
    }
    //--- end::open stream by team chart ---

    //--- start::watchers by team chart ---
    let watchersByTeamChart = false;
    const watchersByTeamChartInit = (data, categories) => {
        var element = document.getElementById("watchersByTeamChart");

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
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed;

                                const formatted = formatToHourMinute(value);
                                return `${label}: ${formatted}`;
                            }
                        }
                    }
                },
            }
        };

        var ctx = element.getContext('2d');
        watchersByTeamChart = new Chart(ctx, config);
    }
    const watchersByTeamChartUpdate = (newData, newCategories) => {
        if (watchersByTeamChart) {
            watchersByTeamChart.data.datasets = newData;
            watchersByTeamChart.data.labels = newCategories;
            watchersByTeamChart.update();
        }
    }
    const fetchWatchersByTeamChartDataAndRender = () => {
        let dateRangePicker = $('[data-watchers-by-team-chart-filter="date_range"]').data('daterangepicker');
        let startDate = dateRangePicker?.startDate.format('YYYY-MM-DD');
        let endDate = dateRangePicker?.endDate.format('YYYY-MM-DD');

        $.ajax({
            url: "{{ route('admin.reports.live-streams.get-watchers-by-team-chart-data') }}",
            method: 'GET',
            data: {
                start_date: startDate,
                end_date: endDate
            },
            beforeSend: function() {
                setLoading(true, watchersByTeamContentArea);
            },
            success: function(res) {
                if (watchersByTeamChart) {
                    watchersByTeamChartUpdate(res.datasets, res.labels);
                } else {
                    watchersByTeamChartInit(res.datasets, res.labels);
                }

                const emptyInfo = watchersByTeamContentArea.find('[data-empty-info]');
                const info = watchersByTeamContentArea.find('[data-info]');


                info.html('');
                res.labels.forEach((label, index) => {
                    const value = res.datasets[0].data[index],
                        bgColor = res.datasets[0].backgroundColor[index];

                    if (value >= 0) {
                        emptyInfo.find('.color').css('background-color', bgColor);
                        emptyInfo.find('.value').text(formatToHourMinute(value));
                        emptyInfo.find('.label').text(label);

                        info.append(emptyInfo.html());
                    }
                })

            },
            error: function(xhr) {
                swal.error({
                    message: xhr.responseJSON?.message ?? null
                })
            },
            complete: function() {
                setLoading(false, watchersByTeamContentArea);
            }
        });
    }
    //--- end::watchers by team chart ---

    //--- start::top gifts ---
    const fetchTopGifts = () => {
        let dateRangePicker = $('[data-top-gifts-chart-filter="date_range"]').data('daterangepicker');
        let startDate = dateRangePicker?.startDate.format('YYYY-MM-DD');
        let endDate = dateRangePicker?.endDate.format('YYYY-MM-DD');

        $.ajax({
            url: "{{ route('admin.reports.live-streams.get-top-gifts') }}",
            method: 'GET',
            data: {
                start_date: startDate,
                end_date: endDate
            },
            beforeSend: function() {
                setLoading(true, topGiftsContentArea);
            },
            success: function(res) {
                const emptyItem = topGiftsContentArea.find('[data-empty-item]');
                const items = topGiftsContentArea.find('[data-items]');

                items.html('');
                res.forEach((item, index) => {
                    emptyItem.find('[data-label]').text(item.channel_name);
                    emptyItem.find('[data-total-coins]').text(item.total_coins_earned);
                    items.append(emptyItem.html());
                })
            },
            error: function() {
                swal.error({
                    message: xhr.responseJSON?.message ?? null
                })
            },
            complete: function() {
                setLoading(false, topGiftsContentArea);
            }
        });
    }
    //--- end::top gifts ---

    //--- start::gifts chart ---
    var giftsChart = {
        self: null,
        rendered: false
    };
    const giftsChartInit = (data, categories) => {
        var element = document.getElementById("gifts-chart");

        if (!element) return;

        var labelColor = KTUtil.getCssVariableValue('--bs-gray-500');
        var borderColor = KTUtil.getCssVariableValue('--bs-gray-200');
        var baseColor = KTUtil.getCssVariableValue('--bs-primary');
        var lightColor = KTUtil.getCssVariableValue('--bs-primary-light');

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
                        return `${formatNumber(val)} adet`;
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

        giftsChart.self = new ApexCharts(element, options);
        giftsChart.rendered = true;
        giftsChart.self.render();
    }
    const giftsChartUpdate = (newData, newCategories) => {
        if (giftsChart.rendered && giftsChart.self) {
            giftsChart.self.updateOptions({
                series: newData,
                xaxis: {
                    categories: newCategories
                }
            });
        }
    }
    const fetchGiftsChartDataAndRender = () => {
        let startDate = $('[data-gifts-chart-filter="start_date"]').val();

        $.ajax({
            url: "{{ route('admin.reports.live-streams.get-gifts-chart-data') }}",
            method: 'GET',
            data: {
                start_date: startDate,
            },
            beforeSend: function() {
                setLoading(true, giftsContentArea);
            },
            success: function(res) {
                if (giftsChart.rendered) {
                    giftsChartUpdate(res.series, res.categories);
                } else {
                    giftsChartInit(res.series, res.categories);
                }
            },
            error: function(xhr) {
                swal.error({
                    message: xhr.responseJSON?.message ?? null
                })
            },
            complete: function() {
                setLoading(false, giftsContentArea);
            }
        });
    }
    //--- end::gifts chart ---

    //--- start::watchers chart ---
    var watchersChart = {
        self: null,
        rendered: false
    };
    const watchersChartInit = (data, categories) => {
        var element = document.getElementById("watchers-chart");

        if (!element) return;

        var labelColor = KTUtil.getCssVariableValue('--bs-gray-500');
        var borderColor = KTUtil.getCssVariableValue('--bs-gray-200');
        var baseColor = KTUtil.getCssVariableValue('--bs-primary');
        var lightColor = KTUtil.getCssVariableValue('--bs-primary-light');

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
                        return `${formatNumber(val)} kişi`;
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

        giftsChart.self = new ApexCharts(element, options);
        giftsChart.rendered = true;
        giftsChart.self.render();
    }
    const watchersChartUpdate = (newData, newCategories) => {
        if (watchersChart.rendered && watchersChart.self) {
            watchersChart.self.updateOptions({
                series: newData,
                xaxis: {
                    categories: newCategories
                }
            });
        }
    }
    const fetchWatchersChartDataAndRender = () => {
        let startDate = $('[data-watchers-chart-filter="start_date"]').val();

        $.ajax({
            url: "{{ route('admin.reports.live-streams.get-watchers-chart-data') }}",
            method: 'GET',
            data: {
                start_date: startDate,
            },
            beforeSend: function() {
                setLoading(true, watchersContentArea);
            },
            success: function(res) {
                if (watchersChart.rendered) {
                    watchersChartUpdate(res.series, res.categories);
                } else {
                    watchersChartInit(res.series, res.categories);
                }
            },
            error: function(xhr) {
                swal.error({
                    message: xhr.responseJSON?.message ?? null
                })
            },
            complete: function() {
                setLoading(false, watchersContentArea);
            }
        });
    }
    //--- end::watchers chart ---

    const refreshAllPageData = () => {
        fetchDurationChartDataAndRender();
        fetchHourChartDataAndRender();
        fetchOpenStreamByTeamChartDataAndRender();
        fetchWatchersByTeamChartDataAndRender();
        fetchTopGifts();
        fetchGiftsChartDataAndRender();
        fetchWatchersChartDataAndRender();
    }

    $(document).ready(function() {
        /* --- start::duration chart --- */
        let durationFirstLoad = true;
        $(document).on("change", "[data-duration-chart-filter]", function() {
            if (durationFirstLoad) {
                durationFirstLoad = false;
                return;
            }
            fetchDurationChartDataAndRender();
        })
        /* --- end::duration chart --- */

        /* --- start::hour chart --- */
        let hourFirstLoad = true;
        $(document).on("change", "[data-hour-chart-filter]", function() {
            if (hourFirstLoad) {
                hourFirstLoad = false;
                return;
            }
            fetchHourChartDataAndRender();
        })
        /* --- end::hour chart --- */

        /* --- start::open stream by team chart --- */
        let openStreamByTeamFirstLoad = true;
        $(document).on("change", "[data-open-stream-by-team-chart-filter]", function() {
            if (openStreamByTeamFirstLoad) {
                openStreamByTeamFirstLoad = false;
                return;
            }
            fetchOpenStreamByTeamChartDataAndRender();
        })
        /* --- end::open stream by team chart --- */

        /* --- start::watchers by team chart --- */
        let watchersByTeamFirstLoad = true;
        $(document).on("change", "[data-watchers-by-team-chart-filter]", function() {
            if (watchersByTeamFirstLoad) {
                watchersByTeamFirstLoad = false;
                return;
            }
            fetchWatchersByTeamChartDataAndRender();
        })
        /* --- end::watchers by team chart --- */

        /* --- start::top gifts chart --- */
        let topGiftsFirstLoad = true;
        $(document).on("change", "[data-top-gifts-chart-filter]", function() {
            if (topGiftsFirstLoad) {
                topGiftsFirstLoad = false;
                return;
            }
            fetchTopGifts();
        })
        /* --- end::top gifts chart --- */

        /* --- start::gifts chart --- */
        let giftsFirstLoad = true;
        $(document).on("change", "[data-gifts-chart-filter]", function() {
            if (giftsFirstLoad) {
                giftsFirstLoad = false;
                return;
            }
            fetchGiftsChartDataAndRender();
        })
        /* --- end::gifts chart --- */

        /* --- start::watchers chart --- */
        let watchersFirstLoad = true;
        $(document).on("change", "[data-watchers-chart-filter]", function() {
            if (watchersFirstLoad) {
                watchersFirstLoad = false;
                return;
            }
            fetchWatchersChartDataAndRender();
        })
        /* --- end::watchers chart --- */

        refreshAllPageData();
    })
</script>
@endsection