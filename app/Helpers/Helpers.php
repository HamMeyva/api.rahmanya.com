<?php

if (!function_exists('assetAdmin')) {
    function assetAdmin($path)
    {
        return url('assets_admin/' . $path);
    }
}
