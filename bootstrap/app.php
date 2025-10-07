<?php

use Illuminate\Http\Request;
use App\Providers\MorphProvider;
use App\Http\Middleware\SetTimezone;
use App\Jobs\ReconcileVideoCounters;
use App\Jobs\UpdateUserInterestsJob;
use App\Providers\AppServiceProvider;
use Illuminate\Support\Facades\Route;
use App\Providers\AuthServiceProvider;
use Illuminate\Foundation\Application;
use App\Providers\AdminServiceProvider;
use App\Providers\AgoraServiceProvider;
use App\Providers\EventServiceProvider;
use App\Jobs\Feed\UpdateAllUserFeedsJob;
use App\Jobs\UpdateActiveUserMetricsJob;
use Illuminate\Console\Scheduling\Schedule;
use App\Providers\VideoEventServiceProvider;
use Illuminate\Auth\AuthenticationException;
use App\Providers\NotificationServiceProvider;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Middleware\RoleMiddleware;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Console\Commands\UpdateVideoEngagementScores;
use App\Jobs\CheckStreamsHeartbeat;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        then: function () {
            Route::middleware(['web', SetTimezone::class])
                ->prefix('admin')
                ->name('admin.')
                ->group(base_path('routes/admin.php'));
        },
        commands: __DIR__ . '/../routes/console.php',
        channels: __DIR__ . '/../routes/channels.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule) {
        // Kullanıcı oturumlarını kaydet ve Redis temizle
        $schedule->command('users:track-inactive')
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground();

        // Kullanıcı takipçi ve takip edilen sayılarını güncelle (her 6 saatte bir)
        $schedule->command('app:update-all-user-follow-counts')
            ->everyOddHour()
            ->withoutOverlapping();

        // Reklam metriklerini reklam tablosuna aktar. (her bir reklam için; toplam izlenme, toplam tıklama)
        $schedule->command('app:flush-ad-metrics-to-ads-table --batch-size=50')
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground();

        $schedule->command(UpdateVideoEngagementScores::class)
            ->hourly()
            ->withoutOverlapping()
            ->runInBackground();

        /*$schedule->job(new CheckStreamsHeartbeat())
            ->everyMinute()
            ->withoutOverlapping();*/


        $schedule->job(new UpdateAllUserFeedsJob())
            ->everyFifteenMinutes()
            ->withoutOverlapping();

        // Process video events from RabbitMQ
        /*$schedule->command('video:process-events')
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground();*/

        // Update user interests every 6 hours
        $schedule->job(new UpdateUserInterestsJob())
            ->everySixHours()
            ->withoutOverlapping();

        // Update metrics for users who have been active in the past day
        $schedule->job(new UpdateActiveUserMetricsJob())
            ->dailyAt('03:00') // Run during lowest traffic
            ->withoutOverlapping();

        // Reconcile video counters daily
        $schedule->job(new ReconcileVideoCounters())
            ->dailyAt('02:00')
            ->withoutOverlapping();

        // Create MongoDB indexes weekly (maintenance)
        $schedule->command('mongodb:create-indexes')
            ->weekly()
            ->sundays()
            ->at('01:00');
    })
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);

        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('admin') || $request->is('admin/*')) {
                return route('admin.auth.login');
            }
        });
        $middleware->redirectUsersTo(function (Request $request) {
            if ($request->is('admin') || $request->is('admin/*')) {
                return route('admin.dashboard.index');
            }
        });
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('admin') || $request->is('admin/*')) {
                return redirect()->route('admin.auth.login');
            }

            return response()->json([
                'message' => 'Kimlik doğrulanmamış'
            ], 401);
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('admin') || $request->is('admin/*')) {
                //404 pagesi yapılabilir return redirect()->route('pages.404');
                dd('Sayfa bulunamadı. (404 pagesi eklenecek)');
            }

            return response()->json([
                'message' => 'Sayfa bulunamadı.'
            ], 404);
        });

        $exceptions->render(function (ValidationException $e) {
            $errors = array_values($e->errors());

            return response()->json([
                'message' => isset($errors[0]) && isset($errors[0][0]) ? $errors[0][0] : 'Form has errors',
                'errors' => $e->errors(),
            ], 422);
        });
    })
    ->withProviders([
        AppServiceProvider::class,
        AuthServiceProvider::class,
        NotificationServiceProvider::class,
        EventServiceProvider::class,
        AgoraServiceProvider::class,
        AdminServiceProvider::class,
        MorphProvider::class,
        VideoEventServiceProvider::class,
    ])
    ->create();
