<div class="card card-flush overflow-hidden h-lg-100 @if ($isLoading) loading @endif" wire:init="loadData">
    <!--begin::Header-->
    <div class="card-header pt-7">
        <!--begin::Title-->
        <h3 class="card-title align-items-start flex-column">
            <span class="card-label fw-bold text-gray-900">Son 30 Günlük Coin Satışları</span>
            <span class="text-gray-500 mt-1 fw-semibold fs-6">Toplam: ₺{{ $totalAmount }}</span>
        </h3>
        <!--end::Title-->

        <!--begin::Toolbar-->
        <div class="card-toolbar">
            <a href="{{ route('admin.payments.index') }}" class="btn btn-secondary btn-sm">Tüm Ödemeleri
                Görüntüle</a>
        </div>
        <!--end::Toolbar-->
    </div>
    <!--end::Header-->

    <!--begin::Card body-->
    <div class="card-body d-flex align-items-end p-0">
        <!--begin::Chart-->
        <div id="coinSalesChart" class="w-100 ps-4 pe-6 h-500px"></div>
        <!--end::Chart-->
    </div>
    <!--end::Card body-->
</div>

@push('scripts')
    <script>
        Livewire.on('coinSalesDataLoaded', eventData => {
            eventData = eventData[0];
            let data = eventData?.data;
            let categories = eventData?.categories;

            setTimeout(() => {
                initCoinSalesChart(data, categories);
            }, 500);
        });

        var initCoinSalesChart = function(data, categories) {
            var element = document.getElementById("coinSalesChart");

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
                        data: data
                    }],
                    chart: {
                        fontFamily: 'inherit',
                        type: 'area',
                        height: height,
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
                        colors: [baseColor]
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
                                return formatCurrency(val);
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
    </script>
@endpush
