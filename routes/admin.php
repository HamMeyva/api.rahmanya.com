<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\CityController;
use App\Http\Controllers\Admin\GiftController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\TeamController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\MusicController;
use App\Http\Controllers\Admin\StoryController;
use App\Http\Controllers\Admin\VideoController;
use App\Http\Controllers\Admin\ArtistController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\ChallengeController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\AdvertiserController;
use App\Http\Controllers\Admin\AppSettingController;
use App\Http\Controllers\Admin\BannedWordController;
use App\Http\Controllers\Admin\LiveStreamController;
use App\Http\Controllers\Admin\PunishmentController;
use App\Http\Controllers\Admin\CoinPackageController;
use App\Http\Controllers\Admin\MusicCategoryController;
use App\Http\Controllers\Admin\PopularSearchController;
use App\Http\Controllers\Admin\ReportProblemController;
use App\Http\Controllers\Admin\AgoraChannelGiftController;
use App\Http\Controllers\Admin\BulkNotificationController;
use App\Http\Controllers\Admin\LiveStreamCategoryController;
use App\Http\Controllers\Admin\CoinWithdrawalPriceController;
use App\Http\Controllers\Admin\CoinWithdrawalRequestController;
use App\Http\Controllers\Admin\Reports\Users\DemographicsController;
use App\Http\Controllers\Admin\Reports\Users\EngagementMetricsController;
use App\Http\Controllers\Admin\Reports\Users\VideoViewDurationsController;
use App\Http\Controllers\Admin\Reports\Video\ContentPerformancesController;
use App\Http\Controllers\Admin\Reports\LiveStreams\LiveStreamReportController;

Route::group(['prefix' => '/auth', 'as' => 'auth.'], function () {
    Route::middleware('guest:admin')->get('/login', [AuthController::class, 'login'])->name('login');
    Route::middleware('guest:admin')->post('/login', [AuthController::class, 'loginPost'])->name('login-post');
    Route::middleware('auth:admin')->post('/log-out', [AuthController::class, 'logOutPost'])->name('logout-post');
});

Route::group(['middleware' => 'auth:admin'], routes: function () {
    Route::get('/', fn() => redirect()->route('admin.dashboard.index'));

    Route::prefix('/dashboard')->name("dashboard.")->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('index');
    });

    Route::get('/profile', [AdminController::class, 'myProfile'])->name('my-profile');


    Route::group(['middleware' => 'permission:user list', 'prefix' => '/users', 'as' => 'users.', 'controller' => UserController::class], function () {
        Route::get('/', 'index')->name('index');
        Route::post('/data-table', 'dataTable')->name('data-table');
        Route::get('/search', 'search')->name('search');

        Route::get('/{id?}', 'show')->name('show');

        Route::post('/{id}/profile-update', 'profileUpdate')->name('profile-update');
        Route::post('/{id}/notification-permission-update', 'notificationPermissionUpdate')->name('notification-permission-update');
        Route::post('/{id}/update-password', 'updatePassword')->name('update-password');

        Route::post('/{id}/ban', 'ban')->name('ban');

        Route::post('/{id}/account-approve', 'accountApprove')->name('account.approve');
        Route::post('/{id}/account-reject', 'accountReject')->name('account.reject');


        Route::group(['prefix' => '/{userId}/sessions', 'as' => 'sessions.'], routes: function () {
            Route::post('data-table', 'sessionsDataTable')->name('data-table');
        });


        Route::group(['prefix' => '/{userId}/devices', 'as' => 'devices.'], routes: function () {
            Route::post('data-table', 'devicesDataTable')->name('data-table');
            Route::post('block/{id?}', 'deviceBlock')->name('block');
        });

        Route::group(['prefix' => '/{userId}/device-logins', 'as' => 'device-logins.'], routes: function () {
            Route::post('data-table', 'deviceLoginsDataTable')->name('data-table');
        });

        Route::group(['prefix' => '/{userId}/punishments', 'as' => 'punishments.'], routes: function () {
            Route::post('get-punishments-by-category', 'getPunishmentsByCategory')->name('get-punishments-by-category');
            Route::post('create', 'createPunishment')->name('create');
            Route::post('data-table', 'punishmentsDataTable')->name('data-table');
        });
    });

    Route::group(['middleware' => 'permission:video list', 'prefix' => '/videos', 'as' => 'videos.', 'controller' => VideoController::class], function () {
        Route::get('/', 'index')->name('index');
        Route::post('/data-table', 'dataTable')->name('data-table');
        Route::get('/show/{id}', 'show')->name('show');
        Route::post('/destroy/{id?}', 'destroy')->name('destroy');
        Route::post('/bulk-destroy', 'bulkDestroy')->name('bulk-destroy');
        Route::post('/fix-seller-videos', 'fixSellerVideos')->name('fix-seller-videos');
        Route::post('{id}/update', 'update')->name('update');

        Route::get('/{id}/get-like-comment-chart-data', 'getLikeCommentChartData')->name('get-like-comment-chart-data');

        Route::group(['prefix' => '/{id}/views', 'as' => 'views.'], routes: function () {
            Route::post('/data-table', 'viewsDataTable')->name('data-table');
        });

        Route::group(['prefix' => '/{id}/likes', 'as' => 'likes.'], routes: function () {
            Route::post('/data-table', 'likesDataTable')->name('data-table');
        });

        Route::group(['prefix' => '/{id}/comments', 'as' => 'comments.'], routes: function () {
            Route::post('/data-table', 'commentsDataTable')->name('data-table');
        });

        Route::group(['prefix' => '/{id}/report-problems', 'as' => 'report-problems.'], routes: function () {
            Route::post('/data-table', 'reportProblemsDataTable')->name('data-table');
        });
    });

    Route::group(['middleware' => 'permission:story  list', 'prefix' => '/stories', 'as' => 'stories.', 'controller' => StoryController::class], function () {
        Route::get('/', 'index')->name('index');
        Route::post('/data-table', 'dataTable')->name('data-table');
        Route::get('/show/{id}', 'show')->name('show');
        Route::post('/destroy/{id?}', 'destroy')->name('destroy');

        Route::get('/{id}/get-view-like-chart-data', 'getViewLikeChartData')->name('get-view-like-chart-data');


        Route::group(['prefix' => '/{id}/views', 'as' => 'views.'], routes: function () {
            Route::post('/data-table', 'viewsDataTable')->name('data-table');
        });

        Route::group(['prefix' => '/{id}/likes', 'as' => 'likes.'], routes: function () {
            Route::post('/data-table', 'likesDataTable')->name('data-table');
        });

        Route::group(['prefix' => '/{id}/report-problems', 'as' => 'report-problems.'], routes: function () {
            Route::post('/data-table', 'reportProblemsDataTable')->name('data-table');
        });
    });

    Route::group(['middleware' => 'permission:live stream list', 'prefix' => 'live-streams', 'as' => 'live-streams.', 'controller' => LiveStreamController::class], function () {
        Route::get('/', 'index')->name('index');
        Route::post('/data-table', 'dataTable')->name('data-table');
        Route::get('/search', 'search')->name('search');
        Route::get('/show/{id?}', 'show')->name('show');
        Route::post('/update/{id?}', 'update')->name('update');

        Route::post('/stop/{id?}', 'stop')->name('stop');
        Route::post('/send-message/{id?}', 'sendMessage')->name('send-message');
    });

    Route::group(['middleware' => 'permission:live stream category list', 'prefix' => 'live-streams/categories', 'as' => 'live-streams.categories.', 'controller' => LiveStreamCategoryController::class], function () {
        Route::get('/', 'index')->name('index');
        Route::post('/data-table', 'dataTable')->name('data-table');
        Route::post('/store', 'store')->name('store');
        Route::get('/show/{id?}', 'show')->name('show');
        Route::post('/update/{id?}', 'update')->name('update');
    });

    Route::group(['middleware' => 'permission:challenge list', 'prefix' => 'challenges', 'as' => 'challenges.', 'controller' => ChallengeController::class], function () {
        Route::get('/', 'index')->name('index');
        Route::post('/data-table', 'dataTable')->name('data-table');
    });

    Route::group(['middleware' => 'permission:payment list', 'prefix' => '/payments', 'as' => 'payments.', 'controller' => PaymentController::class], function () {
        Route::get('/', 'index')->name('index');
        Route::post('/data-table', 'dataTable')->name('data-table');

        Route::middleware('permission:payment waiting approval list')->get('/waiting-approval', 'waitingApproval')->name('waiting-approval');
        Route::middleware('permission:payment waiting approval list')->post('/waiting-approval/data-table', 'waitingApprovalDataTable')->name('waiting-approval.data-table');

        Route::middleware('permission:payment approve list')->post('/approve/{id?}', 'approve')->name('approve');
        Route::middleware('permission:payment reject list')->post('/reject/{id?}', 'reject')->name('reject');
    });

    Route::group(['middleware' => 'permission:gift list', 'prefix' => '/gifts'], function () {
        Route::group(['prefix' => '/packages', 'as' => 'gifts.', 'controller' => GiftController::class], function () {
            Route::get('/', 'index')->name('index');
            Route::post('/data-table', 'dataTable')->name('data-table');
            Route::get('/create', 'create')->name('create');
            Route::post('/store', 'store')->name('store');
            Route::get('/edit/{gift}', 'edit')->name('edit');
            Route::post('/update/{gift}', 'update')->name('update');
            Route::post('/destroy/{id?}', 'destroy')->name('destroy');
        });

        Route::group(['prefix' => '/agora-channel-gifts', 'as' => 'agora-channel-gifts.', 'controller' => AgoraChannelGiftController::class], function () {
            Route::get('/', 'index')->name('index');
            Route::post('/data-table', 'dataTable')->name('data-table');
        });
    });

    Route::group(['prefix' => '/shoot-coins'], function () {
        Route::group(['prefix' => '/coin-packages', 'as' => 'coin-packages.', 'controller' => CoinPackageController::class], function () {
            Route::get('/', 'index')->name('index');
            Route::post('/data-table', 'dataTable')->name('data-table');
            Route::post('/store', 'store')->name('store');
            Route::get('/show/{id?}', 'show')->name('show');
            Route::post('/update/{id?}', 'update')->name('update');
            Route::post('/destroy/{id?}', 'destroy')->name('destroy');
        });

        Route::group(['prefix' => '/withdrawal-prices', 'as' => 'coin-withdrawal-prices.', 'controller' => CoinWithdrawalPriceController::class], function () {
            Route::get('/', 'index')->name('index');
            Route::post('/bulk-update', 'bulkUpdate')->name('bulk-update');
        });

        Route::group(['prefix' => '/withdrawal-requests', 'as' => 'coin-withdrawal-requests.', 'controller' => CoinWithdrawalRequestController::class], function () {
            Route::get('/', 'index')->name('index');
            Route::post('/data-table', 'dataTable')->name('data-table');
            Route::get('/show/{id}', 'show')->name('show');

            Route::post('/approve/{id}', 'approve')->name('approve');
            Route::post('/reject/{id}', 'reject')->name('reject');
        });
    });

    Route::group(['middleware' => 'permission:report problem list', 'prefix' => '/report-problems', 'as' => 'report-problems.', 'controller' => ReportProblemController::class], function () {
        Route::get('/', 'index')->name('index');
        Route::post('/data-table', 'dataTable')->name('data-table');
        Route::get('/show/{id?}', 'show')->name('show');
        Route::post('/update/{id?}', 'update')->name('update');
    });

    Route::group(['middleware' => 'permission:send bulk notification', 'prefix' => '/bulk-notifications', 'as' => 'bulk-notifications.', 'controller' => BulkNotificationController::class], function () {
        Route::get('/', 'index')->name('index');
        Route::post('/send-sms', 'sendSms')->name('send-sms');
        Route::post('/send-email', 'sendEmail')->name('send-email');
        Route::post('/send-push', 'sendPush')->name('send-push');
    });

    Route::group(['middleware' => 'permission:coupon code list', 'prefix' => '/coupons', 'as' => 'coupons.', 'controller' => CouponController::class], function () {
        Route::get('/', 'index')->name('index');
        Route::post('/data-table', 'dataTable')->name('data-table');
        Route::get('/create', 'create')->name('create');
        Route::post('/store', 'store')->name('store');
        Route::get('/edit/{coupon}', 'edit')->name('edit');
        Route::post('/update/{coupon}', 'update')->name('update');
        Route::post('/destroy/{id?}', 'destroy')->name('destroy');
    });

    Route::group(['prefix' => '/advertisers', 'as' => 'advertisers.', 'controller' => AdvertiserController::class], function () {
        Route::get('/', 'index')->name('index');
        Route::post('/data-table', 'dataTable')->name('data-table');
        Route::post('/store', 'store')->name('store');
        Route::get('/show/{id}', 'show')->name('show');
        Route::post('/update/{id?}', 'update')->name('update');

        Route::get('/search', 'search')->name('search');

        Route::get('/get-create-advertiser-form', 'getCreateAdvertiserForm')->name('get-create-advertiser-form');
        Route::get('/get-edit-advertiser-form/{id?}', 'getEditAdvertiserForm')->name('get-edit-advertiser-form');
    });

    Route::group(['prefix' => '/ads', 'as' => 'ads.', 'controller' => AdController::class], function () {
        Route::get('/', 'index')->name('index');
        Route::post('/data-table', 'dataTable')->name('data-table');
        Route::get('/create', 'create')->name('create');
        Route::post('/store', 'store')->name('store');
        Route::get('/show/{id}', 'show')->name('show');
        Route::get('/edit/{id?}', 'edit')->name('edit');
        Route::post('/update/{id?}', 'update')->name('update');
        Route::post('/status-update/{id?}', 'statusUpdate')->name('status-update');
        Route::post('/payment-status-update/{id?}', 'paymentStatusUpdate')->name('payment-status-update');


        Route::get('/get-stats-data', 'getStatsData')->name('get-stats-data');
    });

    Route::group(['prefix' => '/settings', 'as' => 'settings.'], function () {
        Route::group(['prefix' => '/teams', 'as' => 'teams.', 'controller' => TeamController::class], routes: function () {
            Route::get('/', 'index')->name('index');
            Route::post('/data-table', 'dataTable')->name('data-table');
            Route::post('/store', 'store')->name('store');
            Route::get('/show/{id?}', 'show')->name('show');
            Route::post('/update/{id?}', 'update')->name('update');
        });

        Route::group(['prefix' => '/punishments', 'as' => 'punishments.', 'controller' => PunishmentController::class], routes: function () {
            Route::get('/', 'index')->name('index');
            Route::post('/data-table', 'dataTable')->name('data-table');
            Route::post('/store', 'store')->name('store');
            Route::get('/show/{id?}', 'show')->name('show');
            Route::post('/update/{id?}', 'update')->name('update');
            Route::post('/destroy/{id?}', 'destroy')->name('destroy');
        });

        Route::group(['prefix' => '/popular-searches', 'as' => 'popular-searches.', 'controller' => PopularSearchController::class], routes: function () {
            Route::get('/', 'index')->name('index');
            Route::post('/data-table', 'dataTable')->name('data-table');
            Route::post('/store', 'store')->name('store');
            Route::get('/show/{id?}', 'show')->name('show');
            Route::post('/update/{id?}', 'update')->name('update');
            Route::post('/destroy/{id?}', 'destroy')->name('destroy');
        });

        Route::group(['prefix' => '/banned-words', 'as' => 'banned-words.', 'controller' => BannedWordController::class], routes: function () {
            Route::get('/', 'index')->name('index');
            Route::post('/data-table', 'dataTable')->name('data-table');
            Route::post('/store', 'store')->name('store');
            Route::get('/show/{id?}', 'show')->name('show');
            Route::post('/update/{id?}', 'update')->name('update');
            Route::post('/destroy/{id?}', 'destroy')->name('destroy');
        });

        Route::group(['prefix' => '/app-settings', 'as' => 'app-settings.', 'controller' => AppSettingController::class], routes: function () {
            Route::get('/', 'index')->name('index');
            Route::post('/update', 'update')->name('update');
        });
    });

    Route::group(['prefix' => '/cities', 'as' => 'cities.'], function () {
        Route::get('/search', [CityController::class, 'search'])->name('search');
    });

    Route::group(['prefix' => '/reports', 'as' => 'reports.'], function () {

        Route::group(['prefix' => '/users', 'as' => 'users.'], function () {
            Route::group(['prefix' => '/demographics', 'as' => 'demographics.', 'controller' => DemographicsController::class], function () {
                Route::get('/', 'index')->name('index');
                Route::get('/get-map-data', 'getMapData')->name('get-map-data');
                Route::get('/get-age-range-chart-data', 'getAgeRangeChartData')->name('get-age-range-chart-data');
                Route::get('/get-gender-chart-data', 'getGenderChartData')->name('get-gender-chart-data');
            });

            Route::group(['prefix' => '/video-view-durations', 'as' => 'video-view-durations.', 'controller' => VideoViewDurationsController::class], function () {
                Route::get('/', 'index')->name('index');
                Route::get('/get-stats', 'getStats')->name('get-stats');
                Route::get('/chart-data', 'chartData')->name('chart-data');
                Route::get('/get-top-viewer-users', 'getTopViewerUsers')->name('get-top-viewer-users');
            });

            Route::group(['prefix' => '/engagement-metrics', 'as' => 'engagement-metrics.', 'controller' => EngagementMetricsController::class], function () {
                Route::get('/', 'index')->name('index');
                Route::get('/get-metrics-data', 'getMetricsData')->name('get-metrics-data');
            });
        });

        Route::group(['prefix' => '/videos', 'as' => 'videos.'], function () {
            Route::group(['prefix' => '/content-performances', 'as' => 'content-performances.', 'controller' => ContentPerformancesController::class], function () {
                Route::get('/', 'index')->name('index');
                Route::get('/get-completed-views-data', 'getCompletedViewsData')->name('get-completed-views-data');
                Route::get('/get-most-views-videos-data', 'getMostViewsVideosData')->name('get-most-views-videos-data');
            });
        });

        Route::group(['prefix' => '/live-streams', 'as' => 'live-streams.', 'controller' => LiveStreamReportController::class], function () {
            Route::get('/', 'index')->name('index');
            Route::get('/get-duration-chart-data', 'getDurationChartData')->name('get-duration-chart-data');
            Route::get('/get-hour-chart-data', 'getHourChartData')->name('get-hour-chart-data');
            Route::get('/get-open-stream-by-team-chart-data', 'getOpenStreamByTeamChartData')->name('get-open-stream-by-team-chart-data');
            Route::get('/get-watchers-by-team-chart-data', 'getWatchersByTeamChartData')->name('get-watchers-by-team-chart-data');
            Route::get('/get-top-gifts', 'getTopGifts')->name('get-top-gifts');
            Route::get('/get-gifts-chart-data', 'getGiftsChartData')->name('get-gifts-chart-data');
            Route::get('/get-watchers-chart-data', 'getWatchersChartData')->name('get-watchers-chart-data');
        });
    });

    Route::group(['prefix' => '/roles', 'as' => 'roles.', 'controller' => RoleController::class], function () {
        Route::middleware('permission:role list')->get('/', 'index')->name('index');
        Route::middleware('permission:role create')->get('/create', 'create')->name('create');
        Route::middleware('permission:role create')->post('/store', 'store')->name('store');
        Route::middleware('permission:role edit')->get('/edit/{role}', 'edit')->name('edit');
        Route::middleware('permission:role edit')->post('/update/{role}', 'update')->name('update');
        Route::middleware('permission:role delete')->post('/delete/{role}', 'delete')->name('delete');
    });

    Route::group(['prefix' => '/admins', 'as' => 'admins.', 'controller' => AdminController::class], function () {
        Route::middleware('permission:admin list')->get('/', 'index')->name('index');
        Route::middleware('permission:admin list')->post('/data-table', 'dataTable')->name('data-table');
        Route::middleware('permission:admin create')->get('/create', 'create')->name('create');
        Route::middleware('permission:admin create')->post('/store', 'store')->name('store');
        Route::middleware('permission:admin edit')->get('/get-admin/{id?}', 'getAdmin')->name('get-admin');
        Route::middleware('permission:admin edit')->post('/update/{id?}', 'update')->name('update');
        Route::middleware('permission:admin delete')->post('/delete/{id?}', 'delete')->name('delete');

        Route::get('/notifications', 'notifications')->name('notifications');
    });

    Route::group(['prefix' => '/musics', 'as' => 'musics.', 'controller' => MusicController::class], function () {
        Route::middleware('permission:music list')->get('/', 'index')->name('index');
        Route::middleware('permission:music list')->post('/data-table', 'dataTable')->name('data-table');
        Route::middleware('permission:music create')->post('/store', 'store')->name('store');
        Route::middleware('permission:music edit')->get('/show/{id?}', 'show')->name('show');
        Route::middleware('permission:music edit')->post('/update/{id?}', 'update')->name('update');
        Route::middleware('permission:music delete')->post('/delete/{id?}', 'delete')->name('delete');
    });

    Route::group(['prefix' => '/musics/categories', 'as' => 'musics.categories.', 'controller' => MusicCategoryController::class], function () {
        Route::middleware('permission:music category list')->get('/', 'index')->name('index');
        Route::middleware('permission:music category list')->post('/data-table', 'dataTable')->name('data-table');
        Route::middleware('permission:music category create')->post('/store', 'store')->name('store');
        Route::middleware('permission:music category edit')->get('/show/{id?}', 'show')->name('show');
        Route::middleware('permission:music category edit')->post('/update/{id?}', 'update')->name('update');
        Route::middleware('permission:music category delete')->post('/delete/{id?}', 'delete')->name('delete');
    });

    Route::group(['prefix' => '/artists', 'as' => 'artists.', 'controller' => ArtistController::class], function () {
        Route::middleware('permission:artist list')->get('/', 'index')->name('index');
        Route::middleware('permission:artist list')->post('/data-table', 'dataTable')->name('data-table');
        Route::middleware('permission:artist create')->post('/store', 'store')->name('store');
        Route::middleware('permission:artist edit')->get('/show/{id?}', 'show')->name('show');
        Route::middleware('permission:artist edit')->post('/update/{id?}', 'update')->name('update');
        Route::middleware('permission:artist delete')->post('/delete/{id?}', 'delete')->name('delete');
    });
});
