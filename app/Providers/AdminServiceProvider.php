<?php

namespace App\Providers;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class AdminServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        if (Request::is('admin/*')) {
            $onlineUserCount = Redis::scard('active-users');
            View::share('onlineUserCount', $onlineUserCount);
        }
    }
}
