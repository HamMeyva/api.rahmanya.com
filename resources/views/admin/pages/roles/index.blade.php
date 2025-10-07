@extends('admin.template')
@section('title', 'Roller')
@section('breadcrumb')
<x-admin.breadcrumb data="Roller" />
@endsection
@section('master')
<div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-5 g-xl-9">
    @can('role create')
    <!--begin::Add new card-->
    <div class="col-md-4">
        <!--begin::Card-->
        <div class="card h-md-100">
            <!--begin::Card body-->
            <div class="card-body d-flex flex-center">
                <!--begin::Button-->
                <a href="{{ route('admin.roles.create') }}" class="btn btn-clear d-flex flex-column flex-center">
                    <!--begin::Illustration-->
                    <img src="{{assetAdmin('media/illustrations/sketchy-1/4.png')}}" alt="" class="mw-100 mh-150px mb-7">
                    <!--end::Illustration-->

                    <!--begin::Label-->
                    <div class="fw-bold fs-3 text-gray-600 text-hover-primary">Yeni Rol Ekle</div>
                    <!--end::Label-->
                </a>
                <!--begin::Button-->
            </div>
            <!--begin::Card body-->
        </div>
        <!--begin::Card-->
    </div>
    <!--begin::Add new card-->
    @endcan

    @foreach ($roles as $role)
    <!--begin::Col-->
    <div class="col-md-4">
        <!--begin::Card-->
        <div class="card card-flush h-md-100">
            <!--begin::Card header-->
            <div class="card-header">
                <!--begin::Card title-->
                <div class="card-title">
                    <h2>{{ $role->name }}</h2>
                </div>
                <!--end::Card title-->
            </div>
            <!--end::Card header-->

            <!--begin::Card body-->
            <div class="card-body pt-1">
                <!--begin::Users-->
                <div class="fw-bold text-gray-600 mb-5">Bu role sahip toplam kullanıcı sayısı: {{ $role->users()->count() }}</div>
                <!--end::Users-->

                <!--begin::Permissions-->
                <div class="d-flex flex-column text-gray-600">
                    @foreach ($role->permissions()->limit(3)->get() as $permission)
                    <div class="d-flex align-items-center py-2"><span class="bullet bg-primary me-3"></span> {{ __("permissions.{$permission->name}") }}</div>
                    @endforeach
                    @if ($role->permissions()->count() > 3)
                    <div class="d-flex align-items-center py-2"><span class="bullet bg-primary me-3"></span> ve {{ $role->permissions()->count() - 3 }} tane daha ...</div>
                    @endif
                </div>
                <!--end::Permissions-->
            </div>
            <!--end::Card body-->

            <!--begin::Card footer-->
            <div class="card-footer flex-wrap pt-0">
                @if ($role->name != 'Super Admin')
                @can('role edit')
                <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-light-primary btn-active-primary btn-sm my-1 me-2">Düzenle</a>
                @endcan
                @can('role delete')
                <x-admin.form-elements.submit-btn class="btn btn-light-danger btn-active-danger btn-sm my-1 me-2 deleteBtn" attr="data-action-url={{ route('admin.roles.delete', ['role' => $role->id]) }}">Sil</x-admin.form-elements.submit-btn>
                @endcan
                @endif
            </div>
            <!--end::Card footer-->
        </div>
        <!--end::Card-->
    </div>
    <!--end::Col-->
    @endforeach
</div>
@endsection
@section('scripts')
<script>
    $(document).ready(function() {
        $(document).on('click', '.deleteBtn', function() {
            const actionUrl = $(this).data('action-url'),
            button = $(this);
            Swal.fire({
                icon: 'warning',
                title: 'Silmek istediğinize emin misiniz?',
                showConfirmButton: true,
                showCancelButton: true,
                allowOutsideClick: false,
                buttonsStyling: false,
                confirmButtonText: 'Sil',
                cancelButtonText: 'Vazgeç',
                customClass: {
                    confirmButton: "btn btn-danger btn-sm",
                    cancelButton: 'btn btn-secondary btn-sm'
                }
            }).then((r) => {
                if (r.isConfirmed) {
                    $.ajax({
                        type: 'POST',
                        url: actionUrl,
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        dataType: 'json',
                        beforeSend: function() {
                            propSubmitButton(button, 1);
                        },
                        success: function(res) {
                            swal.success({
                                message: res.message
                            }).then(() => window.location.reload())
                        },
                        error: function(xhr) {
                            swal.error({
                                message: xhr?.responseJSON?.message ?? null
                            })
                        },
                        complete: function() {
                            propSubmitButton(button, 0);
                        }
                    })
                }
            })
        })
    })
</script>
@endsection