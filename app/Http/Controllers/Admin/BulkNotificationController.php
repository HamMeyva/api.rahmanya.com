<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Notifications\BulkNotification;
use Illuminate\Support\Facades\Notification;

class BulkNotificationController extends Controller
{
    public function index()
    {
        return view('admin.pages.bulk-notifications.index');
    }

    public function sendSms(Request $request): JsonResponse
    {
        $request->validate([
            'country_id' => 'required|exists:countries,id',
            'city_id' => 'nullable|exists:cities,id',
            'sms.body' => 'required|string|max:255',
        ], [
            'country_id.required' => __('validation.required', ['attribute' => "Ülke"]),
            'sms.body.required' => __('validation.required', ['attribute' => 'Sms Metni']),
            'sms.body.max' => __('validation.max.string', ['attribute' => 'Sms Metni', 'max' => 255]),
        ]);

        $countryId = $request->input('country_id');
        $cityIds = $request->input('city_ids');
        $title = null;
        $body = $request->input('sms.body');

        $query = $this->getUsersQuery('general_sms_notification', $countryId, $cityIds);

        $userCount = $query->count();

        $query->chunk(1000, function ($users) use ($title, $body) {
            Notification::send($users, new BulkNotification('sms', $title, $body));
        });

        return response()->json([
            'message' => "Smsler gönderiliyor. Toplam kullanıcı: {$userCount}",
        ]);
    }

    public function sendEmail(Request $request): JsonResponse
    {
        $request->validate([
            'country_id' => 'required|exists:countries,id',
            'city_id' => 'nullable|exists:cities,id',
            'email.title' => 'required',
            'email.body' => 'required',
        ], [
            'country_id.required' => __('validation.required', ['attribute' => "Ülke"]),
            'email.title.required' => __('validation.required', ['attribute' => 'Konu']),
            'email.body.required' => __('validation.required', ['attribute' => 'E-Posta Metni']),
        ]);

        $countryId = $request->input('country_id');
        $cityIds = $request->input('city_ids');
        $title = $request->input('email.title');
        $body = $request->input('email.body');

        $query = $this->getUsersQuery('general_email_notification', $countryId, $cityIds);

        $userCount = $query->count();

        $query->chunk(1000, function ($users) use ($title, $body) {
            Notification::send($users, new BulkNotification('mail', $title, $body));
        });

        return response()->json([
            'message' => "E-postalar gönderiliyor. Toplam kullanıcı: {$userCount}",
        ]);
    }

    public function sendPush(Request $request): JsonResponse
    {
        $request->validate([
            'country_id' => 'required|exists:countries,id',
            'city_id' => 'nullable|exists:cities,id',
            'push.title' => 'required',
            'push.body' => 'required|string|max:255',
        ], [
            'country_id.required' => __('validation.required', ['attribute' => "Ülke"]),
            'push.title.required' => __('validation.required', ['attribute' => "Başlık"]),
            'push.body.required' => __('validation.required', ['attribute' => 'Bildirim Metni']),
            'push.body.max' => __('validation.max.string', ['attribute' => 'Bildirim Metni', 'max' => 255]),
        ]);

        $countryId = $request->input('country_id');
        $cityIds = $request->input('city_ids');
        $title = $request->input('push.title');
        $body = $request->input('push.body');

        $query = $this->getUsersQuery('general_push_notification', $countryId, $cityIds);

        $userCount = $query->count();

        $query->chunk(1000, function ($users) use ($title, $body) {
            Notification::send($users, new BulkNotification('push', $title, $body));
        });

        return response()->json([
            'message' => "Anlık bildirimler gönderiliyor. Toplam kullanıcı: {$userCount}",
        ]);
    }

    protected function getUsersQuery($channel, $countryId, $cityIds = null, $teamId = null): Builder
    {
        return User::query()
            ->where($channel, true)
            ->where('country_id', $countryId)
            ->when($cityIds, function ($query, $cityIds) {
                $query->whereIn('city_id', $cityIds);
            })
            ->when($teamId, function ($query, $teamId) {
                $query->where('team_id', $teamId);
            });
    }
}
