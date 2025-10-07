@props([
    "placeholder" =>'Tabloda Ara',
    "attr" => "",
    "class" => ""
])
<div class="d-flex align-items-center position-relative my-1">
    <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5">
        <span class="path1"></span>
        <span class="path2"></span>
    </i>
    <input type="text" {{$attr}}
           class="form-control w-250px ps-13 {{$class}}"
           placeholder="{{$placeholder}}"/>
</div>
