@extends('admin.template')
@section('title', 'Katılım Metrikleri')
@section('breadcrumb')
    <x-admin.breadcrumb data="Katılım Metrikleri" />
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
            <div class="d-flex flex-end">
                <div style="width: 210px;">
                    <label class="form-label fs-7">Tarih Aralığı</label>
                    <x-admin.form-elements.date-range-picker customAttr="data-filter=date_range"
                        customClass="form-control-sm" />
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <!--begin::Card-->
            <div class="card card-stretch pb-5">
                <div class="card-body position-relative loading" data-content-area="sport_videos">
                    <!-- begin::Header-->
                    <div data-loading style="display: none;">
                        <span class="spinner-border spinner-border-sm align-middle text-primary border-2"></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-5">
                        <h4>Spor Videoları</h4>
                    </div>
                    <!-- end::Header-->
                    <!-- begin::Body-->
                    <div>
                        <!--begin::Item-->
                        <div class="d-flex flex-stack">
                            <!--begin::Section-->
                            <div class="text-gray-700 fw-semibold fs-6 me-2">Beğeni</div>
                            <!--end::Section-->
                            <!--begin::Statistics-->
                            <div class="d-flex align-items-senter">
                                <i class="ki-duotone ki-arrow-up-right fs-2 text-success me-2">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                <!--begin::Number-->
                                <span class="text-gray-900 fw-bolder fs-6"
                                    data-value-area="sport_videos_like_count">0</span>
                                <!--end::Number-->
                            </div>
                            <!--end::Statistics-->
                        </div>
                        <!--end::Item-->
                        <!--begin::Separator-->
                        <div class="separator separator-dashed my-3"></div>
                        <!--end::Separator-->
                        <!--begin::Item-->
                        <div class="d-flex flex-stack">
                            <!--begin::Section-->
                            <div class="text-gray-700 fw-semibold fs-6 me-2">Yorum</div>
                            <!--end::Section-->
                            <!--begin::Statistics-->
                            <div class="d-flex align-items-senter">
                                <i class="ki-duotone ki-arrow-up-right fs-2 text-success me-2">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                <!--begin::Number-->
                                <span class="text-gray-900 fw-bolder fs-6"
                                    data-value-area="sport_videos_comment_count">0</span>
                                <!--end::Number-->
                            </div>
                            <!--end::Statistics-->
                        </div>
                        <!--end::Item-->
                        <!--begin::Separator-->
                        <div class="separator separator-dashed my-3"></div>
                        <!--end::Separator-->
                        <!--begin::Item-->
                        <div class="d-flex flex-stack">
                            <!--begin::Section-->
                            <div class="text-gray-700 fw-semibold fs-6 me-2">Paylaşım</div>
                            <!--end::Section-->
                            <!--begin::Statistics-->
                            <div class="d-flex align-items-senter">
                                <i class="ki-duotone ki-arrow-up-right fs-2 text-success me-2">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                <!--begin::Number-->
                                <span class="text-gray-900 fw-bolder fs-6"
                                    data-value-area="sport_videos_share_count">0</span>
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
        <div class="col-xl-6">
            <!--begin::Card-->
            <div class="card card-stretch pb-5">
                <div class="card-body position-relative loading" data-content-area="other_videos">
                    <!-- begin::Header-->
                    <div data-loading style="display: none;">
                        <span class="spinner-border spinner-border-sm align-middle text-primary border-2"></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-5">
                        <h4>Diğer Videolar</h4>
                    </div>
                    <!-- end::Header-->
                    <!-- begin::Body-->
                    <div>
                        <!--begin::Item-->
                        <div class="d-flex flex-stack">
                            <!--begin::Section-->
                            <div class="text-gray-700 fw-semibold fs-6 me-2">Beğeni</div>
                            <!--end::Section-->
                            <!--begin::Statistics-->
                            <div class="d-flex align-items-senter">
                                <i class="ki-duotone ki-arrow-up-right fs-2 text-success me-2">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                <!--begin::Number-->
                                <span class="text-gray-900 fw-bolder fs-6"
                                    data-value-area="other_videos_like_count">0</span>
                                <!--end::Number-->
                            </div>
                            <!--end::Statistics-->
                        </div>
                        <!--end::Item-->
                        <!--begin::Separator-->
                        <div class="separator separator-dashed my-3"></div>
                        <!--end::Separator-->
                        <!--begin::Item-->
                        <div class="d-flex flex-stack">
                            <!--begin::Section-->
                            <div class="text-gray-700 fw-semibold fs-6 me-2">Yorum</div>
                            <!--end::Section-->
                            <!--begin::Statistics-->
                            <div class="d-flex align-items-senter">
                                <i class="ki-duotone ki-arrow-up-right fs-2 text-success me-2">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                <!--begin::Number-->
                                <span class="text-gray-900 fw-bolder fs-6"
                                    data-value-area="other_videos_comment_count">0</span>
                                <!--end::Number-->
                            </div>
                            <!--end::Statistics-->
                        </div>
                        <!--end::Item-->
                        <!--begin::Separator-->
                        <div class="separator separator-dashed my-3"></div>
                        <!--end::Separator-->
                        <!--begin::Item-->
                        <div class="d-flex flex-stack">
                            <!--begin::Section-->
                            <div class="text-gray-700 fw-semibold fs-6 me-2">Paylaşım</div>
                            <!--end::Section-->
                            <!--begin::Statistics-->
                            <div class="d-flex align-items-senter">
                                <i class="ki-duotone ki-arrow-up-right fs-2 text-success me-2">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                <!--begin::Number-->
                                <span class="text-gray-900 fw-bolder fs-6"
                                    data-value-area="other_videos_share_count">0</span>
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
        <div class="col-12 col-xl-6 col-xxl-3">
            <!--begin::Card-->
            <div class="card card-stretch pb-5">
                <div class="card-body position-relative loading" data-content-area="sport_videos">
                    <!-- begin::Header-->
                    <div data-loading style="display: none;">
                        <span class="spinner-border spinner-border-sm align-middle text-primary border-2"></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-5">
                        <h4>Spor Videoları <br> Tamamlanma Oranı</h4>
                        <div>
                            <h2><span data-value-area="sport_videos_completion_rate">0</span>%</h2>
                        </div>
                    </div>
                    <!-- end::Header-->
                    <!-- begin::Body-->
                    <div>
                        <!--begin::Item-->
                        <div class="d-flex flex-stack">
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
                        <div class="d-flex flex-stack">
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
        <div class="col-12 col-xl-6 col-xxl-3">
            <!--begin::Card-->
            <div class="card card-stretch pb-5">
                <div class="card-body position-relative loading" data-content-area="other-videos">
                    <!-- begin::Header-->
                    <div data-loading style="display: none;">
                        <span class="spinner-border spinner-border-sm align-middle text-primary border-2"></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-5">
                        <h4>Spor Videoları <br> Adil İzlenme Oranı</h4>
                        <div>
                            <h2><span data-value-area="sport_videos_viewing_rate">0</span>%</h2>
                        </div>
                    </div>
                    <!-- end::Header-->
                    <!-- begin::Body-->
                    <div>
                        <!--begin::Item-->
                        <div class="d-flex flex-stack">
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
                        <div class="d-flex flex-stack">
                            <!--begin::Section-->
                            <div class="text-gray-700 fw-semibold fs-6 me-2">Adil İzlenen Video</div>
                            <!--end::Section-->
                            <!--begin::Statistics-->
                            <div class="d-flex align-items-senter">
                                <!--begin::Number-->
                                <span class="text-gray-900 fw-bolder fs-6"
                                    data-value-area="sport_videos_total_fair_impression">0</span>
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
        <div class="col-12 col-xl-6 col-xxl-3">
            <!--begin::Card-->
            <div class="card card-stretch pb-5">
                <div class="card-body position-relative loading" data-content-area="other-videos">
                    <!-- begin::Header-->
                    <div data-loading style="display: none;">
                        <span class="spinner-border spinner-border-sm align-middle text-primary border-2"></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-5">
                        <h4>Diğer Videolar <br> Tamamlanma Oranı</h4>
                        <div>
                            <h2><span data-value-area="other_videos_completion_rate">0</span>%</h2>
                        </div>
                    </div>
                    <!-- end::Header-->
                    <!-- begin::Body-->
                    <div>
                        <!--begin::Item-->
                        <div class="d-flex flex-stack">
                            <!--begin::Section-->
                            <div class="text-gray-700 fw-semibold fs-6 me-2">Toplam İzlenme</div>
                            <!--end::Section-->
                            <!--begin::Statistics-->
                            <div class="d-flex align-items-senter">
                                <!--begin::Number-->
                                <span class="text-gray-900 fw-bolder fs-6"
                                    data-value-area="other_videos_total_views">0</span>
                                <!--end::Number-->
                            </div>
                            <!--end::Statistics-->
                        </div>
                        <!--end::Item-->
                        <!--begin::Separator-->
                        <div class="separator separator-dashed my-3"></div>
                        <!--end::Separator-->
                        <!--begin::Item-->
                        <div class="d-flex flex-stack">
                            <!--begin::Section-->
                            <div class="text-gray-700 fw-semibold fs-6 me-2">Tamamı İzlenen Video</div>
                            <!--end::Section-->
                            <!--begin::Statistics-->
                            <div class="d-flex align-items-senter">
                                <!--begin::Number-->
                                <span class="text-gray-900 fw-bolder fs-6"
                                    data-value-area="other_videos_total_completed_views">0</span>
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
        <div class="col-12 col-xl-6 col-xxl-3">
            <!--begin::Card-->
            <div class="card card-stretch pb-5">
                <div class="card-body position-relative loading" data-content-area="other-videos">
                    <!-- begin::Header-->
                    <div data-loading style="display: none;">
                        <span class="spinner-border spinner-border-sm align-middle text-primary border-2"></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-5">
                        <h4>Diğer Videolar <br> Adil İzlenme Oranı</h4>
                        <div>
                            <h2><span data-value-area="other_videos_viewing_rate">0</span>%</h2>
                        </div>
                    </div>
                    <!-- end::Header-->
                      <!-- begin::Body-->
                      <div>
                        <!--begin::Item-->
                        <div class="d-flex flex-stack">
                            <!--begin::Section-->
                            <div class="text-gray-700 fw-semibold fs-6 me-2">Toplam İzlenme</div>
                            <!--end::Section-->
                            <!--begin::Statistics-->
                            <div class="d-flex align-items-senter">
                                <!--begin::Number-->
                                <span class="text-gray-900 fw-bolder fs-6"
                                    data-value-area="other_videos_total_views">0</span>
                                <!--end::Number-->
                            </div>
                            <!--end::Statistics-->
                        </div>
                        <!--end::Item-->
                        <!--begin::Separator-->
                        <div class="separator separator-dashed my-3"></div>
                        <!--end::Separator-->
                        <!--begin::Item-->
                        <div class="d-flex flex-stack">
                            <!--begin::Section-->
                            <div class="text-gray-700 fw-semibold fs-6 me-2">Adil İzlenen Video</div>
                            <!--end::Section-->
                            <!--begin::Statistics-->
                            <div class="d-flex align-items-senter">
                                <!--begin::Number-->
                                <span class="text-gray-900 fw-bolder fs-6"
                                    data-value-area="other_videos_total_fair_impression">0</span>
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
    </div>
@endsection
@section('scripts')
    <script>
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

        const fetchMetricsData = () => {
            let dateRangePicker = $('[data-filter="date_range"]').data('daterangepicker');
            let startDate = dateRangePicker?.startDate.format('YYYY-MM-DD');
            let endDate = dateRangePicker?.endDate.format('YYYY-MM-DD');

            $.ajax({
                url: "{{ route('admin.reports.users.engagement-metrics.get-metrics-data') }}",
                method: 'GET',
                data: {
                    start_date: startDate,
                    end_date: endDate
                },
                beforeSend: function() {
                    setLoading(true);
                },
                success: function(res) {
                    $('[data-value-area="sport_videos_like_count"]').text(res?.sport_videos?.like_count ??
                        0);
                    $('[data-value-area="sport_videos_comment_count"]').text(res?.sport_videos
                        ?.comment_count ?? 0);
                    $('[data-value-area="sport_videos_share_count"]').text(res?.sport_videos?.share_count ??
                        0);

                    $('[data-value-area="other_videos_like_count"]').text(res?.other_videos?.like_count ??
                        0);
                    $('[data-value-area="other_videos_comment_count"]').text(res?.other_videos
                        ?.comment_count ?? 0);
                    $('[data-value-area="other_videos_share_count"]').text(res?.other_videos?.share_count ??
                        0);

                    $('[data-value-area="sport_videos_completion_rate"]').text(res?.sport_videos
                        ?.view_metrics?.completed_rate ?? 0);
                    $('[data-value-area="sport_videos_viewing_rate"]').text(res?.sport_videos?.view_metrics
                        ?.viewing_rate ?? 0);
                    $('[data-value-area="other_videos_completion_rate"]').text(res?.other_videos
                        ?.view_metrics?.completed_rate ?? 0);
                    $('[data-value-area="other_videos_viewing_rate"]').text(res?.other_videos?.view_metrics
                        ?.viewing_rate ?? 0);


                    $('[data-value-area="sport_videos_total_views"]').text(res?.sport_videos
                        ?.view_metrics
                        ?.total_views ?? 0);

                    $('[data-value-area="sport_videos_total_completed_views"]').text(res?.sport_videos
                        ?.view_metrics
                        ?.total_completed_views ?? 0);


                    $('[data-value-area="sport_videos_total_fair_impression"]').text(res?.sport_videos
                        ?.view_metrics
                        ?.total_fair_impression ?? 0);


                    $('[data-value-area="other_videos_total_views"]').text(res?.other_videos
                        ?.view_metrics
                        ?.total_views ?? 0);

                    $('[data-value-area="other_videos_total_completed_views"]').text(res?.other_videos
                        ?.view_metrics
                        ?.total_completed_views ?? 0);

                    $('[data-value-area="other_videos_total_fair_impression"]').text(res?.other_videos
                        ?.view_metrics
                        ?.total_fair_impression ?? 0);
                },
                error: function(xhr) {
                    swal.error({
                        message: xhr.responseJSON?.message ?? null
                    })
                },
                complete: function() {
                    setLoading(false);
                }
            });
        }

        $(document).ready(function() {
            fetchMetricsData();

            let metricsFirstLoad = true;
            $(document).on('change', '[data-filter="date_range"]', function() {
                if (metricsFirstLoad) {
                    metricsFirstLoad = false;
                    return;
                }
                fetchMetricsData();
            })
        })
    </script>
@endsection
