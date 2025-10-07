@props([
    'name' => 'dateInput',
    'placeholder' => '&nbsp;',
    'required' => '',
    'value' => '',
    'customAttr' => '',
    'customClass' => '',
      ])
@php
    use App\Helpers\CommonHelper;
@endphp
<div class="position-relative d-flex align-items-center">
    <!--begin::Icon-->
    <i class="ki-duotone ki-calendar-8 fs-2 position-absolute mx-4">
      <span class="path1"></span>
        <span class="path2"></span>
        <span class="path3"></span>
        <span class="path4"></span>
        <span class="path5"></span>
        <span class="path6"></span>
    </i>
    <!--end::Icon-->
    <!--begin::Datepicker-->
    <input class="form-control ps-12 dateInput {{ $customClass }}"
           name="{{$name}}" value="{{$value ? date((new CommonHelper)->defaultDateFormat(), strtotime($value)) : null}}"
           placeholder="{!! $placeholder !!}" {{$required}} {{ $customAttr }} />
    <!--end::Datepicker-->
</div>
