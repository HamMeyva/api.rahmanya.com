@extends('admin.template')
@section('title', 'Ana Sayfa')
@section('styles')
    <style>
        .apexcharts-title-text {
            font-weight: 600 !important;
            font-size: 18px;
        }

        .card.loading {
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
@section('description', '')
@section('keywords', '')
@section('breadcrumb')
    <x-admin.breadcrumb data="Ana Sayfa" />
@endsection
@section('master')
    <div class="row g-5 gx-xl-10 mb-5 mb-xl-10">
        <!--begin::Col-->
        <div class="col-md-6 col-lg-6 col-xl-6 col-xxl-3 mb-md-5 mb-xl-10">
            <!--begin::Card-->
            <livewire:dashboard.daily-gifts-card />
            <!--end::Card-->

            <!--begin::Card-->
            <div x-data="{ show: false }" x-init="setTimeout(() => show = true, 50)">
                <template x-if="show">
                    <livewire:dashboard.daily-live-streams-card />
                </template>
            </div>
            <!--end::Card-->
        </div>
        <!--end::Col-->

        <!--begin::Col-->
        <div class="col-md-6 col-lg-6 col-xl-6 col-xxl-3 mb-md-5 mb-xl-10">
            <!--begin::Card-->
            <div x-data="{ show: false }" x-init="setTimeout(() => show = true, 100)">
                <template x-if="show">
                    <livewire:dashboard.daily-sales-card />
                </template>
            </div>
            <!--end::Card-->

            <!--begin::Card-->
            <livewire:dashboard.daily-videos-card />
            <!--end::Card-->
        </div>
        <!--end::Col-->

        <!--begin::Col-->
        <div class="col-xxl-6">
            <!--begin::Card-->
            <div x-data="{ show: false }" x-init="setTimeout(() => show = true, 150)">
                <template x-if="show">
                    <livewire:dashboard.coin-sales-chart-card />
                </template>
            </div>
            <!--end::Card-->
        </div>
        <!--end::Col-->
    </div>


    <!--begin::Row-->
    <div class="row gy-5 g-xl-10">
        <!--begin::Col-->
        <div class="col-xl-4">
            <!--begin::Card-->
            <livewire:dashboard.latest-registered-users-card />
            <!--end::Card-->
        </div>
        <!--end::Col-->
        <!--begin::Col-->
        <div class="col-xl-8">
            <!--begin::Card-->
            <livewire:dashboard.coin-packages-sales-card />
            <!--end::Card-->
        </div>
        <!--end::Col-->
    </div>

    <div class="row mb-20 g-10 pt-10">
        <div class="col-xl-6">
            <!--begin::Card-->
            <div x-data="{ show: false }" x-init="setTimeout(() => show = true, 300)">
                <template x-if="show">
                    <livewire:dashboard.active-users-chart />
                </template>
            </div>
            <!--end::Card-->
        </div>
        <div class="col-xl-6">
            <!--begin::Card-->
            <div x-data="{ show: false }" x-init="setTimeout(() => show = true, 300)">
                <template x-if="show">
                    <livewire:dashboard.registered-user-count-chart />
                </template>
            </div>
            <!--end::Card-->
        </div>
        <div class="col-xl-6">
            <!--begin::Card-->
            <div x-data="{ show: false }" x-init="setTimeout(() => show = true, 400)">
                <template x-if="show">
                    <livewire:dashboard.video-upload-chart />
                </template>
            </div>
            <!--end::Card-->
        </div>
        <div class="col-xl-6">
            <!--begin::Card-->
            <div x-data="{ show: false }" x-init="setTimeout(() => show = true, 400)">
                <template x-if="show">
                    <livewire:dashboard.story-activities-chart />
                </template>
            </div>
            <!--end::Card-->
        </div>
        <div class="col-xl-6">
            <!--begin::Card-->
            <div x-data="{ show: false }" x-init="setTimeout(() => show = true, 500)">
                <template x-if="show">
                    <livewire:dashboard.live-stream-activities-chart />
                </template>
            </div>
            <!--end::Card-->
        </div>
        <div class="col-xl-6">
            <!--begin::Card-->
            <div x-data="{ show: false }" x-init="setTimeout(() => show = true, 500)">
                <template x-if="show">
                    <livewire:dashboard.challenge-activities-chart />
                </template>
            </div>
            <!--end::Card-->
        </div>
    </div>
    <!--end::Row-->
@endsection
@section('scripts')
    <script>
        $(document).ready(function() {
            /*const authId = "9ef352fa-06dc-4cfb-b3b9-f343c7df5726";
            Echo.private(`App.Models.User.${authId}`)
                .notification((e) => {
                    console.log(e);
                }).listen('App.Events.UserOnline', (e) => {
                    console.log(e, 'UserOnline');
                });*/
        })
    </script>
    <script>
        /* start::Charts */
        var initChartsWidget1 = function() {
            var element = document.getElementById("chart1");

            if (!element) {
                return;
            }

            var chart = {
                self: null,
                rendered: false
            };

            var initChart = function() {
                var height = parseInt(KTUtil.css(element, 'height'));
                var labelColor = KTUtil.getCssVariableValue('--bs-gray-500');
                var borderColor = KTUtil.getCssVariableValue('--bs-gray-200');
                var baseColor = KTUtil.getCssVariableValue('--bs-primary');
                var secondaryColor = KTUtil.getCssVariableValue('--bs-gray-300');

                var options = {
                    series: [{
                        name: 'Net Profit',
                        data: [44, 55, 57, 56, 61, 58]
                    }, {
                        name: 'Revenue',
                        data: [76, 85, 101, 98, 87, 105]
                    }],
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
                        categories: ['Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
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
                            }
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
                                return "$" + val + " thousands"
                            }
                        }
                    },
                    colors: [baseColor, secondaryColor],
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

                chart.self = new ApexCharts(element, options);
                chart.self.render();
                chart.rendered = true;
            }

            // Init chart
            initChart();

            // Update chart on theme mode change
            KTThemeMode.on("kt.thememode.change", function() {
                if (chart.rendered) {
                    chart.self.destroy();
                }

                initChart();
            });
        }

        var initChartsWidget2 = function() {
            var element = document.getElementById("chart2");

            if (!element) {
                return;
            }

            var chart = {
                self: null,
                rendered: false
            };

            var initChart = function() {
                var height = parseInt(KTUtil.css(element, 'height'));
                var labelColor = KTUtil.getCssVariableValue('--bs-gray-500');
                var borderColor = KTUtil.getCssVariableValue('--bs-gray-200');
                var baseColor = KTUtil.getCssVariableValue('--bs-warning');
                var secondaryColor = KTUtil.getCssVariableValue('--bs-gray-300');

                var options = {
                    series: [{
                        name: 'Net Profit',
                        data: [44, 55, 57, 56, 61, 58]
                    }, {
                        name: 'Revenue',
                        data: [76, 85, 101, 98, 87, 105]
                    }],
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
                            borderRadius: 4
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
                        categories: ['Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
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
                            }
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
                                return "$" + val + " thousands"
                            }
                        }
                    },
                    colors: [baseColor, secondaryColor],
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

                chart.self = new ApexCharts(element, options);
                chart.self.render();
                chart.rendered = true;
            }

            // Init chart
            initChart();

            // Update chart on theme mode change
            KTThemeMode.on("kt.thememode.change", function() {
                if (chart.rendered) {
                    chart.self.destroy();
                }

                initChart();
            });
        }

        var initChartsWidget3 = function() {
            var element = document.getElementById("chart3");

            if (!element) {
                return;
            }

            var chart = {
                self: null,
                rendered: false
            };

            var initChart = function() {
                var height = parseInt(KTUtil.css(element, 'height'));
                var labelColor = KTUtil.getCssVariableValue('--bs-gray-500');
                var borderColor = KTUtil.getCssVariableValue('--bs-gray-200');
                var baseColor = KTUtil.getCssVariableValue('--bs-info');
                var lightColor = KTUtil.getCssVariableValue('--bs-info-light');

                var options = {
                    series: [{
                        name: 'Net Satış',
                        data: [30, 40, 40, 90, 90, 70]
                    }, {
                        name: 'Net Satış 22',
                        data: [22, 11, 32, 1, 3, 2]
                    }],
                    chart: {
                        fontFamily: 'inherit',
                        type: 'area',
                        height: 350,
                        toolbar: {
                            show: false
                        }
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
                        colors: [baseColor]
                    },
                    xaxis: {
                        categories: ['Nis', 'May', 'Haz', 'Tem', 'Agu', 'Eyl'],
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
                                return "₺" + val + ""
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
                chart.self.render();
                chart.rendered = true;
            }

            // Init chart
            initChart();

            // Update chart on theme mode change
            KTThemeMode.on("kt.thememode.change", function() {
                if (chart.rendered) {
                    chart.self.destroy();
                }

                initChart();
            });
        }



        var initChartsWidget4 = function() {
            var element = document.getElementById("chart4");

            if (!element) {
                return;
            }

            var chart = {
                self: null,
                rendered: false
            };

            var initChart = function() {
                var height = parseInt(KTUtil.css(element, 'height'));
                var labelColor = KTUtil.getCssVariableValue('--bs-gray-500');
                var borderColor = KTUtil.getCssVariableValue('--bs-gray-200');

                var baseColor = KTUtil.getCssVariableValue('--bs-success');
                var baseLightColor = KTUtil.getCssVariableValue('--bs-success-light');
                var secondaryColor = KTUtil.getCssVariableValue('--bs-warning');
                var secondaryLightColor = KTUtil.getCssVariableValue('--bs-warning-light');

                var options = {
                    series: [{
                        name: 'Net Profit',
                        data: [60, 50, 80, 40, 100, 60]
                    }, {
                        name: 'Revenue',
                        data: [70, 60, 110, 40, 50, 70]
                    }],
                    chart: {
                        fontFamily: 'inherit',
                        type: 'area',
                        height: 350,
                        toolbar: {
                            show: false
                        }
                    },
                    plotOptions: {},
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
                        curve: 'smooth'
                    },
                    xaxis: {
                        categories: ['Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
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
                                color: labelColor,
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
                                return "$" + val + " thousands"
                            }
                        }
                    },
                    colors: [baseColor, secondaryColor],
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
                        colors: [baseLightColor, secondaryLightColor],
                        strokeColor: [baseLightColor, secondaryLightColor],
                        strokeWidth: 3
                    }
                };

                chart.self = new ApexCharts(element, options);
                chart.self.render();
                chart.rendered = true;
            }

            // Init chart
            initChart();

            // Update chart on theme mode change
            KTThemeMode.on("kt.thememode.change", function() {
                if (chart.rendered) {
                    chart.self.destroy();
                }

                initChart();
            });
        }

        var initChartsWidget5 = function() {
            var element = document.getElementById("chart5");

            if (!element) {
                return;
            }

            var chart = {
                self: null,
                rendered: false
            };

            var initChart = function() {
                var height = parseInt(KTUtil.css(element, 'height'));
                var labelColor = KTUtil.getCssVariableValue('--bs-gray-500');
                var borderColor = KTUtil.getCssVariableValue('--bs-gray-200');

                var baseColor = KTUtil.getCssVariableValue('--bs-primary');
                var secondaryColor = KTUtil.getCssVariableValue('--bs-info');

                var options = {
                    series: [{
                        name: 'Net Profit',
                        data: [40, 50, 65, 70, 50, 30]
                    }, {
                        name: 'Revenue',
                        data: [-30, -40, -55, -60, -40, -20]
                    }],
                    chart: {
                        fontFamily: 'inherit',
                        type: 'bar',
                        stacked: true,
                        height: 350,
                        toolbar: {
                            show: false
                        }
                    },
                    plotOptions: {
                        bar: {
                            horizontal: false,
                            columnWidth: ['12%'],
                            borderRadius: [6, 6]
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
                        categories: ['Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
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
                        min: -80,
                        max: 80,
                        labels: {
                            style: {
                                colors: labelColor,
                                fontSize: '12px'
                            }
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
                                return "$" + val + " thousands"
                            }
                        }
                    },
                    colors: [baseColor, secondaryColor],
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

                chart.self = new ApexCharts(element, options);
                chart.self.render();
                chart.rendered = true;
            }

            // Init chart
            initChart();

            // Update chart on theme mode change
            KTThemeMode.on("kt.thememode.change", function() {
                if (chart.rendered) {
                    chart.self.destroy();
                }

                initChart();
            });
        }

        var initChartsWidget6 = function() {
            var element = document.getElementById("chart6");

            if (!element) {
                return;
            }

            var chart = {
                self: null,
                rendered: false
            };

            var initChart = function() {
                var height = parseInt(KTUtil.css(element, 'height'));
                var labelColor = KTUtil.getCssVariableValue('--bs-gray-500');
                var borderColor = KTUtil.getCssVariableValue('--bs-gray-200');

                var baseColor = KTUtil.getCssVariableValue('--bs-primary');
                var baseLightColor = KTUtil.getCssVariableValue('--bs-primary-light');
                var secondaryColor = KTUtil.getCssVariableValue('--bs-info');

                var options = {
                    series: [{
                        name: 'Net Profit',
                        type: 'bar',
                        stacked: true,
                        data: [40, 50, 65, 70, 50, 30]
                    }, {
                        name: 'Revenue',
                        type: 'bar',
                        stacked: true,
                        data: [20, 20, 25, 30, 30, 20]
                    }, {
                        name: 'Expenses',
                        type: 'area',
                        data: [50, 80, 60, 90, 50, 70]
                    }],
                    chart: {
                        fontFamily: 'inherit',
                        stacked: true,
                        height: 350,
                        toolbar: {
                            show: false
                        }
                    },
                    plotOptions: {
                        bar: {
                            stacked: true,
                            horizontal: false,
                            borderRadius: 4,
                            columnWidth: ['12%']
                        },
                    },
                    legend: {
                        show: false
                    },
                    dataLabels: {
                        enabled: false
                    },
                    stroke: {
                        curve: 'smooth',
                        show: true,
                        width: 2,
                        colors: ['transparent']
                    },
                    xaxis: {
                        categories: ['Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
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
                        max: 120,
                        labels: {
                            style: {
                                colors: labelColor,
                                fontSize: '12px'
                            }
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
                                return "$" + val + " thousands"
                            }
                        }
                    },
                    colors: [baseColor, secondaryColor, baseLightColor],
                    grid: {
                        borderColor: borderColor,
                        strokeDashArray: 4,
                        yaxis: {
                            lines: {
                                show: true
                            }
                        },
                        padding: {
                            top: 0,
                            right: 0,
                            bottom: 0,
                            left: 0
                        }
                    }
                };

                chart.self = new ApexCharts(element, options);
                chart.self.render();
                chart.rendered = true;
            }

            // Init chart
            initChart();

            // Update chart on theme mode change
            KTThemeMode.on("kt.thememode.change", function() {
                if (chart.rendered) {
                    chart.self.destroy();
                }

                initChart();
            });
        }

        var initChartsWidget7 = function() {
            var element = document.getElementById("chart7");

            if (!element) {
                return;
            }

            var chart = {
                self: null,
                rendered: false
            };

            var initChart = function() {

                var height = parseInt(KTUtil.css(element, 'height'));

                var labelColor = KTUtil.getCssVariableValue('--bs-gray-500');
                var borderColor = KTUtil.getCssVariableValue('--bs-gray-200');
                var strokeColor = KTUtil.getCssVariableValue('--bs-gray-300');

                var color1 = KTUtil.getCssVariableValue('--bs-warning');
                var color1Light = KTUtil.getCssVariableValue('--bs-warning-light');

                var color2 = KTUtil.getCssVariableValue('--bs-success');
                var color2Light = KTUtil.getCssVariableValue('--bs-success-light');

                var color3 = KTUtil.getCssVariableValue('--bs-primary');
                var color3Light = KTUtil.getCssVariableValue('--bs-primary-light');

                var options = {
                    series: [{
                        name: 'Net Profit',
                        data: [30, 30, 50, 50, 35, 35]
                    }, {
                        name: 'Revenue',
                        data: [55, 20, 20, 20, 70, 70]
                    }, {
                        name: 'Expenses',
                        data: [60, 60, 40, 40, 30, 30]
                    }],
                    chart: {
                        fontFamily: 'inherit',
                        type: 'area',
                        height: height,
                        toolbar: {
                            show: false
                        },
                        zoom: {
                            enabled: false
                        },
                        sparkline: {
                            enabled: true
                        }
                    },
                    plotOptions: {},
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
                        width: 2,
                        colors: [color1, 'transparent', 'transparent']
                    },
                    xaxis: {
                        categories: ['Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
                        axisBorder: {
                            show: false,
                        },
                        axisTicks: {
                            show: false
                        },
                        labels: {
                            show: false,
                            style: {
                                colors: labelColor,
                                fontSize: '12px'
                            }
                        },
                        crosshairs: {
                            show: false,
                            position: 'front',
                            stroke: {
                                color: strokeColor,
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
                            show: false,
                            style: {
                                colors: labelColor,
                                fontSize: '12px'
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
                                return "$" + val + " thousands"
                            }
                        }
                    },
                    colors: [color1, color2, color3],
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
                        colors: [color1Light, color2Light, color3Light],
                        strokeColor: [color1, color2, color3],
                        strokeWidth: 3
                    }
                };

                chart.self = new ApexCharts(element, options);
                chart.self.render();
                chart.rendered = true;
            }

            // Init chart
            initChart();

            // Update chart on theme mode change
            KTThemeMode.on("kt.thememode.change", function() {
                if (chart.rendered) {
                    chart.self.destroy();
                }

                initChart();
            });
        }

        var initChartsWidget8 = function() {
            var element = document.getElementById("chart8");

            if (!element) {
                return;
            }

            var chart = {
                self: null,
                rendered: false
            };

            var initChart = function() {
                var height = parseInt(KTUtil.css(element, 'height'));

                var labelColor = KTUtil.getCssVariableValue('--bs-gray-500');
                var borderColor = KTUtil.getCssVariableValue('--bs-gray-200');
                var strokeColor = KTUtil.getCssVariableValue('--bs-gray-300');

                var color1 = KTUtil.getCssVariableValue('--bs-warning');
                var color1Light = KTUtil.getCssVariableValue('--bs-warning-light');

                var color2 = KTUtil.getCssVariableValue('--bs-success');
                var color2Light = KTUtil.getCssVariableValue('--bs-success-light');

                var color3 = KTUtil.getCssVariableValue('--bs-primary');
                var color3Light = KTUtil.getCssVariableValue('--bs-primary-light');

                var options = {
                    series: [{
                        name: 'Net Profit',
                        data: [30, 30, 50, 50, 35, 35]
                    }, {
                        name: 'Revenue',
                        data: [55, 20, 20, 20, 70, 70]
                    }, {
                        name: 'Expenses',
                        data: [60, 60, 40, 40, 30, 30]
                    }, ],
                    chart: {
                        fontFamily: 'inherit',
                        type: 'area',
                        height: height,
                        toolbar: {
                            show: false
                        },
                        zoom: {
                            enabled: false
                        },
                        sparkline: {
                            enabled: true
                        }
                    },
                    plotOptions: {},
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
                        width: 2,
                        colors: [color1, color2, color3]
                    },
                    xaxis: {
                        x: 0,
                        offsetX: 0,
                        offsetY: 0,
                        padding: {
                            left: 0,
                            right: 0,
                            top: 0,
                        },
                        categories: ['Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
                        axisBorder: {
                            show: false,
                        },
                        axisTicks: {
                            show: false
                        },
                        labels: {
                            show: false,
                            style: {
                                colors: labelColor,
                                fontSize: '12px'
                            }
                        },
                        crosshairs: {
                            show: false,
                            position: 'front',
                            stroke: {
                                color: strokeColor,
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
                        y: 0,
                        offsetX: 0,
                        offsetY: 0,
                        padding: {
                            left: 0,
                            right: 0
                        },
                        labels: {
                            show: false,
                            style: {
                                colors: labelColor,
                                fontSize: '12px'
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
                                return "$" + val + " thousands"
                            }
                        }
                    },
                    colors: [color1Light, color2Light, color3Light],
                    grid: {
                        borderColor: borderColor,
                        strokeDashArray: 4,
                        padding: {
                            top: 0,
                            bottom: 0,
                            left: 0,
                            right: 0
                        }
                    },
                    markers: {
                        colors: [color1, color2, color3],
                        strokeColor: [color1, color2, color3],
                        strokeWidth: 3
                    }
                };

                chart.self = new ApexCharts(element, options);
                chart.self.render();
                chart.rendered = true;
            }

            // Init chart
            initChart();

            // Update chart on theme mode change
            KTThemeMode.on("kt.thememode.change", function() {
                if (chart.rendered) {
                    chart.self.destroy();
                }

                initChart();
            });
        }

        initChartsWidget1();
        initChartsWidget2();
        initChartsWidget3();
        initChartsWidget4();
        initChartsWidget5();
        initChartsWidget6();
        initChartsWidget7();
        initChartsWidget8();
        /* end::Charts */
    </script>
@endsection

<style>
    .loading {
        filter: blur(1px);
        opacity: 0.3;
        pointer-events: none;
        position: relative;
    }

    /* Loader spinner */
    .loading::after {
        content: "";
        position: absolute;
        top: 50%;
        left: 50%;
        width: 20px;
        height: 20px;
        margin: -10px 0 0 -10px;
        border: 4px solid #28a745;
        /* yeşil ton */
        border-top-color: transparent;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        z-index: 10;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }
</style>
