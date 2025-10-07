@extends('admin.template')
@section('title', 'İçerik Performansları')
@section('breadcrumb')
    <x-admin.breadcrumb data="İçerik Performansları" />
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
            <!--begin::Card-->
            <div class="card card-stretch pb-5">
                <div class="card-body position-relative loading" data-content-area="completed_views">
                    <!-- begin::Header-->
                    <div data-loading style="display: none;">
                        <span class="spinner-border spinner-border-sm align-middle text-primary border-2"></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-5">
                        <div>
                            <h4>Tamamı İzlenen Video Oranı</h4>
                            <h2><span data-value-area="videos_completion_rate">0</span>%</h2>
                        </div>
                        <div>
                            <div style="width: 210px;">
                                <x-admin.form-elements.date-range-picker customClass="form-control-sm"
                                    customAttr="data-completed-views-filter=date_range" />
                            </div>
                        </div>
                    </div>
                    <!-- end::Header-->
                    <!-- begin::Body-->
                    <div>
                        <!--begin::Item-->
                        <div class="d-flex flex-column">
                            <!--begin::Section-->
                            <div class="text-gray-700 fw-semibold fs-6 me-2">Toplam İzlenme</div>
                            <!--end::Section-->
                            <!--begin::Statistics-->
                            <div class="d-flex align-items-senter">
                                <!--begin::Number-->
                                <span class="text-gray-900 fw-bolder fs-6"
                                    data-value-area="sport_videos_total_views">0</span>
                                <!--end::Number-->
                            </div>
                            <!--end::Statistics-->
                        </div>
                        <!--end::Item-->
                        <!--begin::Separator-->
                        <div class="separator separator-dashed my-3"></div>
                        <!--end::Separator-->
                        <!--begin::Item-->
                        <div class="d-flex flex-column">
                            <!--begin::Section-->
                            <div class="text-gray-700 fw-semibold fs-6 me-2">Tamamı İzlenen Video</div>
                            <!--end::Section-->
                            <!--begin::Statistics-->
                            <div class="d-flex align-items-senter">
                                <!--begin::Number-->
                                <span class="text-gray-900 fw-bolder fs-6"
                                    data-value-area="sport_videos_total_completed_views">0</span>
                                <!--end::Number-->
                            </div>
                            <!--end::Statistics-->
                        </div>
                        <!--end::Item-->
                    </div>
                    <!-- end::Body-->
                </div>
            </div>
            <!--end::Card-->
        </div>
        <div class="col-12">
            <!--begin::Card-->
            <div class="card card-stretch pb-5">
                <div class="card-body position-relative loading" data-content-area="most_views_videos">
                    <!-- begin::Header-->
                    <div data-loading style="display: none;">
                        <span class="spinner-border spinner-border-sm align-middle text-primary border-2"></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-5">
                        <div>
                            <h4>En Çok İzlenen Videolar</h4>
                            <span class="text-muted fs-7 fw-bold">Top 10</span>
                        </div>
                        <div>
                            <div style="width: 210px;">
                                <x-admin.form-elements.date-range-picker customAttr="data-most-views-videos-filter=date_range"
                                customClass="form-control-sm" />
                            </div>
                        </div>
                    </div>
                    <!-- end::Header-->
                    <!-- begin::Body-->
                    <div>
                        <div class="table-responsive">
                            <!--begin::Table-->
                            <table class="table table-row-dashed gy-3 align-middle">
                                <!--begin::Table head-->
                                <thead>
                                    <tr class="fs-7 fw-bold text-gray-500">
                                        <th class="min-w-175px text-start">VİDEO</th>
                                        <th class="min-w-100px">KULLANICI</th>
                                        <th class="min-w-100px text-center">İZLENME</th>
                                        <th class="w-50px text-center">GÖRÜNTÜLE</th>
                                    </tr>
                                </thead>
                                <!--end::Table head-->
                                <!--begin::Table body-->
                                <tbody class="fw-semibold text-gray-800" data-most-views-videos-body></tbody>
                                <!--end::Table body-->
                            </table>
                        </div>
                    </div>
                    <!-- end::Body-->
                </div>
            </div>
            <!--end::Card-->
        </div>
    </div>
@endsection
@section('scripts')
    <script>
        const mostViewsVideosContentArea = $('[data-content-area="most_views_videos"]'),
            completedViewsContentArea = $('[data-content-area="completed_views"]');

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

        const fetchCompletedViewsData = () => {
            let dateRangePicker = $('[data-completed-views-filter="date_range"]').data('daterangepicker');
            let startDate = dateRangePicker?.startDate.format('YYYY-MM-DD');
            let endDate = dateRangePicker?.endDate.format('YYYY-MM-DD');

            $.ajax({
                url: "{{ route('admin.reports.videos.content-performances.get-completed-views-data') }}",
                method: 'GET',
                data: {
                    start_date: startDate,
                    end_date: endDate
                },
                beforeSend: function() {
                    setLoading(true, completedViewsContentArea);
                },
                success: function(res) {
                    $('[data-value-area="sport_videos_total_views"]').html(formatNumber((res?.total_views ?? 0), 'dot'));
                    $('[data-value-area="sport_videos_total_completed_views"]').html(formatNumber((res?.completed_views ?? 0), 'dot'));
                    $('[data-value-area="videos_completion_rate"]').html(res?.completion_rate ?? 0);
                },
                error: function(xhr) {
                    swal.error({
                        message: xhr.responseJSON?.message ?? null
                    })
                },
                complete: function() {
                    setLoading(false, completedViewsContentArea);
                }
            });
        }

        const fetchMostViewsVideosData = () => {
            let dateRangePicker = $('[data-most-views-videos-filter="date_range"]').data('daterangepicker');
            let startDate = dateRangePicker?.startDate.format('YYYY-MM-DD');
            let endDate = dateRangePicker?.endDate.format('YYYY-MM-DD');

            $.ajax({
                url: "{{ route('admin.reports.videos.content-performances.get-most-views-videos-data') }}",
                method: 'GET',
                data: {
                    start_date: startDate,
                    end_date: endDate
                },
                beforeSend: function() {
                    setLoading(true, mostViewsVideosContentArea);
                },
                success: function(res) {
                    $('[data-most-views-videos-body]').html(res?.html ?? '');
                },
                error: function(xhr) {
                    swal.error({
                        message: xhr.responseJSON?.message ?? null
                    })
                },
                complete: function() {
                    setLoading(false, mostViewsVideosContentArea);
                }
            });
        }


        const refreshAllPageData = () => {
            fetchMostViewsVideosData();
            fetchCompletedViewsData();
        }

        $(document).ready(function() {
            refreshAllPageData();

            let mostViewsVideosFirstLoad = true;
            $(document).on('change', '[data-most-views-videos-filter="date_range"]', function() {
                if (mostViewsVideosFirstLoad) {
                    mostViewsVideosFirstLoad = false;
                    return;
                }
                fetchMostViewsVideosData();
            })

            let completedViewsFirstLoad = true;
            $(document).on('change', '[data-completed-views-filter="date_range"]', function() {
                if (completedViewsFirstLoad) {
                    completedViewsFirstLoad = false;
                    return;
                }
                fetchCompletedViewsData();
            })
        })
    </script>
@endsection
