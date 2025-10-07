@extends('admin.template')
@use('App\Helpers\CommonHelper')
@use('Carbon\Carbon')
@use('App\Models\Morph\ReportProblem')
@section('title', (new CommonHelper())->limitText($reportProblem->message))
@section('breadcrumb')
<x-admin.breadcrumb :data="[
        (new CommonHelper())->limitText($reportProblem->message),
        'Şikayetler' => route('admin.report-problems.index'),
    ]" :backUrl="route('admin.report-problems.index')" />
@endsection
@section('master')
<!--begin::Layout-->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <!--begin::Heading-->
                <div class="d-flex align-items-center mb-12">
                    <!--begin::Icon-->
                    <i class="ki-duotone ki-file-added fs-4qx ms-n2 me-3" style="color: {{ ReportProblem::$statusColors[$reportProblem->status_id] }}">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    <!--end::Icon-->
                    <!--begin::Content-->
                    <div class="d-flex flex-column">
                        <!--begin::Title-->
                        <h1 class="text-gray-800 fw-semibold">{{ $reportProblem->report_problem_category->name }}</h1>
                        <!--end::Title-->
                        <!--begin::Info-->
                        <div class="">
                            <!--begin::Label-->
                            <span class="fw-semibold text-muted me-6">
                                Şikayet Türü: <span class="text-muted">{{ $reportProblem->get_entity_type }}</span>
                            </span>
                            <!--end::Label-->
                            <!--begin::Label-->
                            <span class="fw-semibold text-muted me-6">Kullanıcı:
                                <a target="_blank"
                                    href="{{ route('admin.users.show', ['id' => $reportProblem->user->id]) }}"
                                    class="text-muted text-hover-primary">{{ $reportProblem->user->full_name }}</a></span>
                            <!--end::Label-->
                            <!--begin::Label-->
                            <span class="fw-semibold text-muted">Oluşturulma Tarihi
                                <span
                                    class="fw-bold text-gray-600 me-1">{{ Carbon::parse($reportProblem->created_at->format('Y-m-d H:i:s'))->diffForHumans() }}</span>({{ $reportProblem->get_created_at }})</span>
                            <!--end::Label-->
                        </div>
                        <!--end::Info-->
                    </div>
                    <!--end::Content-->
                </div>
                <!--end::Heading-->
                <!--begin::Details-->
                <div class="mb-20">
                    <!--begin::Description-->
                    <div class="mb-15 fs-5 fw-normal text-gray-800">
                        {!! $reportProblem->message !!}
                    </div>
                    <!--end::Description-->
                </div>
                <!--end::Details-->

                <!--begin::Row-->
                <form id="primaryForm" class="row gap-5">

                    <div class="col-12 d-flex justify-content-between align-items-center">
                        <div>
                            @if ($reportProblem->get_entity_url)
                            <a target="_blank" href="{{ $reportProblem->get_entity_url }}" class="btn btn-primary"><i class="fa fa-eye"></i> Şikayet İçeriğini Görüntüle</a>
                            @endif
                        </div>
                        <div class="d-flex flex-end gap-5">
                        <div class="w-200px">
                            <label class="form-label d-flex justify-content-between">
                                İlgilenen Admin
                                @if (!$reportProblem->admin_id)
                                <span class="badge badge-sm badge-success cursor-pointer assignYourselfBtn">Kendini Ata</span>
                                @endif
                            </label>
                            <x-admin.form-elements.admin-select :selectedOption="$reportProblem->admin_id" placeholder="Admin" :allowClear="true" name="admin_id"
                                :value="$reportProblem->admin_id" />
                        </div>
                        <div class="w-200px">
                            <label class="form-label">Durum</label>
                            <x-admin.form-elements.report-problem-status-select :selectedOption="$reportProblem->status_id" placeholder="Durum" :hideSearch="true" name="status_id"
                                :value="$reportProblem->status_id" />
                        </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <textarea class="form-control placeholder-gsray-600 fw-bold fs-4 ps-9 pt-7" rows="6" name="admin_response"
                            placeholder="Admin Notu">{!! $reportProblem->admin_response !!}</textarea>
                    </div>
                    <div class="col-12">
                        <!--begin::Submit-->
                        <x-admin.form-elements.submit-btn
                            class="float-end">Değişiklikleri Kaydet</x-admin.form-elements.submit-btn>
                        <!--end::Submit-->
                    </div>
            </div>
            <!--end::Row-->
        </div>
    </div>
</div>
</div>
<!--end::Layout-->
@endsection
@section('scripts')
<script>
    $(document).ready(function() {
        $(document).on('click', '.assignYourselfBtn', function() {
            $(this).closest('div').find('select').val('{{ auth()->user()->id }}').trigger('change');
        });

        $(document).on("submit", "#primaryForm", function(e) {
            e.preventDefault();

            let formData = new FormData(this)
            submitBtn = $(this).find("[type='submit']");

            $.ajax({
                type: 'POST',
                url: "{{ route('admin.report-problems.update', ['id' => $reportProblem->id]) }}",
                data: formData,
                dataType: 'json',
                contentType: false,
                processData: false,
                cache: false,
                beforeSend: function() {
                    propSubmitButton(submitBtn, 1);
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
                    propSubmitButton(submitBtn, 0);
                }
            })
        })
    })
</script>
@endsection