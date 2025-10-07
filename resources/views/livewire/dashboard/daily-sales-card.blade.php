<div class="card card-flush h-md-50 mb-5 mb-xl-10" wire:init="loadData">
    <!--begin::Card body-->
    <div class="card-body d-flex flex-column justify-content-between @if ($isLoading) loading @endif"
        wire:loading.class="loading">
        <div>
            <!--begin::Info-->
            <div class="d-flex align-items-center">
                <!--begin::Currency-->
                <span class="fs-4 fw-semibold text-gray-500 me-1 align-self-start">₺</span>
                <!--end::Currency-->
                <!--begin::Amount-->
                <span
                    class="fs-2hx fw-bold text-gray-900 me-2 lh-1 ls-n2">{{ number_format($totalAmount ?? 0, 2, ',', '.') }}</span>
                <!--end::Amount-->
            </div>
            <!--end::Info-->
            <!--begin::Subtitle-->
            <span class="text-gray-500 pt-1 fw-semibold fs-6">Bugünün Toplam Satışları</span>
            <!--end::Subtitle-->
        </div>

        <div>
            <!--begin::Chart-->
            <div id="weeklySalesChart" class="w-100 border border-1 border-dashed rounded-3"
                style="height: 150px;"></div>
            <!--end::Chart-->
        </div>
    </div>
    <!--end::Card body-->
</div>
@push('scripts')
    <script>
        $(document).ready(function() {
            Livewire.on('weeklySalesChartDataLoaded', eventData => {
                eventData = eventData[0];
                let data = eventData?.data;
                let categories = eventData?.categories;

                setTimeout(() => {
                    chartInit(data, categories);
                }, 500);
            });

            let chart = {
                self: null,
                rendered: false
            };
            const chartInit = (data, categories) => {
                var element = document.getElementById("weeklySalesChart");

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
                        },
                        zoom: {
                            enabled: false
                        },
                        selection: {
                            enabled: false
                        },
                        animations: {
                            enabled: false
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
                        floating: true,
                        axisBorder: {
                            show: false,
                        },
                        axisTicks: {
                            show: false
                        },
                        labels: {
                            show: false,
                        }
                    },
                    yaxis: {
                        show: false
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
                                return formatCurrency(val);
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
        })
    </script>
@endpush
