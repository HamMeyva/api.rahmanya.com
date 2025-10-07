<div class="card card-flush" wire:init="loadData">
    <!--begin::Header-->
    <div class="card-header pt-7">
        <!--begin::Title-->
        <h3 class="card-title align-items-start flex-column">
            <span class="card-label fw-bold text-gray-900">Aktif Kullanıcılar</span>
        </h3>
        <!--end::Title-->
    </div>
    <!--end::Header-->
    <!--begin::Body-->
    <div class="card-body {{ $isLoading ? 'loading' : '' }}">
        <div id="activeUsersChart" class="h-250px"></div>
    </div>
    <!--end::Body-->
</div>
@push('scripts')
    <script>
        $(document).ready(function() {
            Livewire.on('activeUsersDataLoaded', eventData => {
                eventData = eventData[0];
                let data = eventData?.data;
                let categories = eventData?.categories;

                setTimeout(() => {
                    chartInit(data, categories);
                }, 500);
            });

            let activeUsersChart = {
                self: null,
                rendered: false
            };
            const chartInit = (data, categories) => {
                var element = document.getElementById("activeUsersChart");

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
                            },
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
                                return formatNumber(val);
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
                                return `${formatNumber(val, 'dot')} kişi`;
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

                activeUsersChart.self = new ApexCharts(element, options);
                activeUsersChart.rendered = true;
                activeUsersChart.self.render();
            }
            const chartUpdate = (newData, newCategories) => {
                if (activeUsersChart.rendered && activeUsersChart.self) {
                    activeUsersChart.self.updateOptions({
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
