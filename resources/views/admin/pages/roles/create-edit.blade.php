@extends('admin.template')
@section('title', $title)
@section('breadcrumb')
<x-admin.breadcrumb :data="[$title, 'Roller' => route('admin.roles.index')]" :backUrl="route('admin.roles.index')" />
@endsection
@section('master')
<div class="card">
    <div class="card-body">
        <form id="primaryForm" action="{{ $submitUrl }}" method="POST" class="row gap-5">
            @csrf
            <div class="col-xl-12">
                <div class="form-label">Rol Adı</div>
                <input type="text" name="name" class="form-control" value="{{ $role?->name ?? null }}">
            </div>
            <div class="col-xl-12">
                <div class="form-label">İzinler</div>
                <div class="row">
                    @foreach($permissionGroups as $groupName => $permissions)
                    @php
                    $groupName = $groupName ?: 'other';
                    @endphp
                    <div class="col-xl-3 border-end border-1 pb-3">
                        <h4>{{__('permissions.' . $groupName)}}</h4>
                        <div>
                            @foreach($permissions as $item)
                            <div class="form-group mb-2">
                                <div class="form-check d-flex mb-0 align-items-center">
                                    <input id="permission_{{$item->id}}" type="checkbox" class="form-check-input"
                                        name="permission[]"
                                        {{ isset($role) && in_array($item->id, $role->permissions->pluck('id')->toArray(), true) ? 'checked' : ''}}
                                        value="{{$item->id}}">
                                    <label for="permission_{{$item->id}}"
                                        class="font-weight-normal fw-normal mb-0 ml-2 ms-2">{{__('permissions.' . $item->name)}}</label>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            <div class="col-12">
                <div class="separator separator-dashed"></div>
            </div>
            <div class="col-12">
                <x-admin.form-elements.submit-btn>{{ isset($role) ? 'Değişiklikleri Kaydet' : 'Oluştur' }}</x-admin.form-elements.submit-btn>
            </div>
        </form>
    </div>
</div>
@endsection
@section('scripts')
<script>
    $(document).ready(function() {

        $(document).on("submit", "#primaryForm", function(e) {
            e.preventDefault();

            let submitButton = $(this).find("[type='submit']");

            $.ajax({
                type: 'POST',
                url: $(this).attr('action'),
                data: new FormData(this),
                dataType: 'json',
                contentType: false,
                processData: false,
                cache: false,
                beforeSend: function() {
                    propSubmitButton(submitButton, 1);
                },
                success: function(res) {
                    swal.success({
                        message: res.message
                    }).then(() => window.location.href = res.redirect_url)
                },
                error: function(xhr) {
                    swal.error({
                        message: xhr.responseJSON?.message ?? null
                    })
                },
                complete: function() {
                    propSubmitButton(submitButton, 0);
                }
            })
        })
    })
</script>
@endsection