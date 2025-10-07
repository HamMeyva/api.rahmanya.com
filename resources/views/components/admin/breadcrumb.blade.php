@props([
"data",
"backUrl" => null
])
@php
if (is_array($data)){
$title = $data[0];
unset($data[0]);
} else{
$title = $data;
}
@endphp
<div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
    <!--begin::Toolbar container-->
    <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
        <!--begin::Page title BreadCrumb-->
        <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
            <!--begin::Title-->
            <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">{{$title}}</h1>
            <!--end::Title-->

            <!--begin::Breadcrumb-->
            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                <!--begin::Item-->
                <li class="breadcrumb-item text-muted">
                    <a href="{{route('admin.dashboard.index')}}"
                        class="text-muted text-hover-primary">{{ config('app.name') }}</a>
                </li>
                <!--end::Item-->
                <!--begin::Item-->
                <li class="breadcrumb-item">
                    <span class="bullet bg-gray-500 w-5px h-2px"></span>
                </li>
                <!--end::Item-->
                @if(is_array($data))
                @foreach($data as $key => $item)
                <!--begin::Item-->
                <li class="breadcrumb-item text-muted">
                    <a href="{{$item}}"
                        class="text-muted text-hover-primary">{{$key}}</a>
                </li>
                <!--end::Item-->
                <!--begin::Item-->
                <li class="breadcrumb-item">
                    <span class="bullet bg-gray-500 w-5px h-2px"></span>
                </li>
                <!--end::Item-->
                @endforeach
                @endif
                <!--begin::Item-->
                <li class="breadcrumb-item text-gray-900">{{$title}}</li>
                <!--end::Item-->
            </ul>
            <!--end::Breadcrumb-->
        </div>
        <!--end::Page title BreadCrumb-->
        @if ($backUrl)
        <div class="d-flex align-items-center gap-2 gap-lg-3">
            <a href="{{ $backUrl }}" class="btn btn-sm fw-bold btn-primary"><i class="fa fa-arrow-left" style="margin-bottom: 1px;"></i>Geri Dön</a>
        </div>
        @endif
    </div>
    <!--end::Toolbar container-->
</div>