<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Punishment;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Helpers\CommonHelper;
use App\Models\UserPunishment;
use App\Models\UserSessionLog;
use App\Models\UserDeviceLogin;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Relations\UserDevice;
use App\Notifications\UserBannedNotification;
use App\Notifications\UserPunishedNotification;
use App\Http\Requests\Admin\User\ProfileUpdateRequest;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UserController extends Controller
{
    public function index()
    {
        return view('admin.pages.users.index');
    }

    public function dataTable(Request $request): JsonResponse
    {
        $query = User::query()->with(['primary_team', 'user_stats', 'gender']);

        $recordsTotal = (clone $query)->count();

        // Search
        if (!empty($request->input('search')['value'])) {
            $search = $request->input('search')['value'];
            $query->where('id', 'LIKE', "%{$search}%")
                ->orWhereHas('primary_team', function ($q) use ($search) {
                    $q->where('name', 'ILIKE', "%{$search}%");
                })
                ->orWhere('email', 'ILIKE', "%{$search}%")
                ->orWhere(DB::raw("CONCAT(name, ' ', surname)"), 'ILIKE', "%{$search}%");
        }

        // Filters
        if ($request->filled('team_id')) {
            $query->where('primary_team_id', $request->input('team_id'));
        }

        if ($request->filled('gender_id')) {
            $query->where('gender_id', $request->input('gender_id'));
        }

        if ($request->boolean('is_frozen')) {
            $query->where('is_frozen', true);
        }

        if ($request->boolean('is_private')) {
            $query->where('is_private', true);
        }

        $recordsFiltered = (clone $query)->count();

        // Order by
        $columns = ['id', 'id', 'name', 'email', 'id', 'gender_id', 'id', 'id', 'id', 'is_frozen', 'is_private'];

        $orderColumnIndex = $request->input('order.0.column');
        $orderColumn = $columns[$orderColumnIndex] ?? 'id';
        $orderDir = $request->input('order.0.dir', 'desc');

        if ($orderColumnIndex === null) {
            $orderColumn = 'id';
            $orderDir = 'desc';
        }

        $query->orderBy($orderColumn, $orderDir);

        // Pagination
        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 10);
        $query->offset($start)->limit($length > 0 ? $length : PHP_INT_MAX);

        //
        $list = $query->get();

        $data = $list->map(function ($item) {
            $avatar = $item->avatar ? '<div class="symbol symbol-50px">
                <span class="symbol-label" style="background-image:url(' . $item->avatar . ');"></span>
            </div>' : '-';

            $userId = "<span class='badge badge-secondary'>{$item->id}</span>";

            $frozenIcon = $item->is_frozen ? 'xmark text-danger' : 'check text-success';
            $privateIcon = $item->is_private ? 'xmark text-danger' : 'check text-success';

            return [
                view('components.link', [
                    'label' => $avatar,
                    'url' => route('admin.users.show', ['id' => $item->id])
                ])->render(),
                $userId,
                view('components.link', [
                    'label' => $item->full_name,
                    'url' => route('admin.users.show', ['id' => $item->id])
                ])->render(),
                $item->email,
                $item?->primary_team?->name ?? '-',
                $item?->gender?->name ?? '-',
                $item->user_stats->video_count ?? 0,
                $item->user_stats->follower_count ?? 0,
                $item->user_stats->following_count ?? 0,
                "<i class='fa fa-{$frozenIcon}'></i>",
                "<i class='fa fa-{$privateIcon}'></i>",
                view('components.admin.action-buttons', [
                    'itemId' => $item->id,
                    'showView' => true,
                    'viewUrl' => route('admin.users.show', ['id' => $item->id]),
                    'showDelete' => true,
                ])->render()
            ];
        });

        return response()->json([
            'draw' => (int) $request->input('draw'),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function show($id)
    {
        $user = User::withTrashed()->find($id);
        if (!$user) {
            throw new NotFoundHttpException();
        }

        return view('admin.pages.users.show', compact('user'));
    }

    /**
     * Kullanıcıyı sil (soft delete)
     */
    public function destroy(string $id): JsonResponse
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json([
                'message' => "Kullanıcı bulunamadı.",
            ], 404);
        }

        // İlişkili verileri temizle
        $user->tokens()->delete(); // Tüm oturumları sonlandır

        // Soft delete uygula
        $user->delete();

        return response()->json([
            'message' => "Kullanıcı başarıyla silindi.",
        ]);
    }

    public function profileUpdate(ProfileUpdateRequest $request, $id): JsonResponse
    {
        $user = User::withTrashed()->find($id);
        if (!$user) {
            throw new NotFoundHttpException();
        }

        $validatedData = $request->validated();

        if ($request->input('avatar_changed') == 1 && $request->hasFile('avatar')) {
            $file = $request->file('avatar');
            $extension = $file->getClientOriginalExtension();
            $guid = Str::uuid();
            $path = "users/{$user->id}/avatar/{$guid}.{$extension}";

            // Upload to BunnyCDN
            $bunnyCdn = new \App\Services\BunnyCdnService();
            $uploadSuccess = $bunnyCdn->uploadToStorage($path, file_get_contents($file->getRealPath()));

            if (!$uploadSuccess) {
                return response()->json([
                    'message' => 'Avatar BunnyCDN\'e yüklenirken bir hata oluştu.'
                ], 500);
            }

            // Get the CDN URL
            $avatarUrl = $bunnyCdn->getStorageUrl($path);
            $validatedData['avatar'] = $avatarUrl;
        }

        if ($request->input('remove_avatar') == 1) {
            $user->deleteAvatar();
            $validatedData['avatar'] = null;
        }

        $teamIds = [];
        foreach ($request->input('team_repeater_condition_area') as $condition) {
            if (!empty($condition['team_id'])) {
                $teamIds[] = $condition['team_id'];
            }
        }

        $user->teams()->sync($teamIds);

        $validatedData['birthday'] = Carbon::parse($validatedData['birthday']);

        $user->update($validatedData);

        return response()->json([
            'message' => 'Profil bilgileri başarıyla güncellendi.'
        ]);
    }

    public function notificationPermissionUpdate(Request $request, $id): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|in:general_email_notification,general_sms_notification,general_push_notification,like_notification,comment_notification,follower_notification,taggable_notification',
            'is_checked' => 'required|boolean',
        ]);

        $user = User::withTrashed()->find($id);
        if (!$user)
            throw new NotFoundHttpException();

        $user->update([
            $request->input('name') => $request->input('is_checked')
        ]);

        return response()->json([
            'message' => 'Bildirim izni başarıyla güncellendi.'
        ]);
    }

    public function updatePassword(Request $request, $id): JsonResponse
    {
        $user = User::withTrashed()->find($id);
        if (!$user) {
            throw new NotFoundHttpException();
        }

        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ], [
            'password.required' => 'Parola gereklidir.',
            'password.string' => 'Parola string olmalıdır.',
            'password.min' => 'Parola en az 8 karakter uzunluğunda olmalıdır.',
            'password.confirmed' => 'Parolalar eşleşmiyor.',
        ]);

        $user->password = $request->input('password');
        $user->save();

        return response()->json([
            'message' => 'Parola başarıyla güncellendi.'
        ]);
    }

    public function ban(Request $request, $id): JsonResponse
    {
        $user = User::withTrashed()->find($id);
        if (!$user) {
            throw new NotFoundHttpException();
        }

        $request->validate([
            'type' => 'required|in:ban,unban',
            'reason' => 'nullable|string|max:255'
        ], [
            'type.required' => 'Sayfayı yenileyip tekrar deneyiniz.',
            'type.in' => 'Sayfayı yenileyip tekrar deneyiniz.',
            'reason.max' => 'Sebep en fazla 255 karakter uzunluğunda olmalıdır.'
        ]);

        if ($request->input('type') == 'ban') {
            $user->is_banned = true;
            $user->banned_at = now();
            $user->ban_reason = $request->input('reason');

            $user->notify(new UserBannedNotification($request->input('reason')));

            $user->tokens()->delete();
        } else {
            $user->is_banned = false;
            $user->banned_at = null;
            $user->ban_reason = null;
        }

        $user->save();

        return response()->json([
            'message' => ($request->input('type') == 'ban' ? 'Kullanıcı engellendi ve tüm cihazlardan çıkış yaptırıldı' : 'Kullanıcının engeli kaldırıldı') . '.'
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $term = $request->term["term"] ?? '';

        $data = User::query()
            ->where(function ($query) use ($term) {
                $query->where('id', 'LIKE', "%{$term}%")
                    ->orWhere('name', 'ILIKE', "%{$term}%")
                    ->orWhere('surname', 'ILIKE', "%{$term}%")
                    ->orWhere(DB::raw("CONCAT(name, ' ', surname)"), 'ILIKE', "%{$term}%");
            })
            ->limit(50)
            ->orderByDesc("id")
            ->get();

        $result = [];
        foreach ($data as $item) {
            $result[] = [
                "id" => $item->id,
                "name" => $item->nickname,
                "extraParams" => $item
            ];
        }

        return response()->json([
            "items" => $result
        ]);
    }

    public function sessionsDataTable(Request $request, $userId): JsonResponse
    {
        $query = UserSessionLog::query()->where('user_id', $userId);

        $recordsTotal = (clone $query)->count();

        // Search
        //if (!empty($request->input('search')['value'])) {
        //$search = $request->input('search')['value'];
        //$query->where('ip', 'ILIKE', "%{$search}%");
        //}

        // Filters
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [$request->input('start_date') . ' 00:00:00', $request->input('end_date') . ' 23:59:59']);
        }

        $recordsFiltered = (clone $query)->count();

        // Order by
        $columns = ['start_at', 'end_at', 'duration'];

        $orderColumnIndex = $request->input('order.0.column');
        $orderColumn = $columns[$orderColumnIndex] ?? 'start_at';
        $orderDir = $request->input('order.0.dir', 'desc');

        if ($orderColumnIndex === null) {
            $orderColumn = 'start_at';
            $orderDir = 'desc';
        }

        $query->orderBy($orderColumn, $orderDir);

        // Pagination
        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 10);
        $query->offset($start)->limit($length > 0 ? $length : PHP_INT_MAX);

        //
        $list = $query->get();

        $data = $list->map(function ($item) {
            $startAt = $item->get_start_at ?? '-';
            $endAt = $item->get_end_at ?? '-';
            $duration = $item->duration ? (new CommonHelper)->formatToHourMinute($item->duration) : '-';

            return [
                "<span class='badge badge-secondary'>{$startAt}</span>",
                "<span class='badge badge-secondary'>{$endAt}</span>",
                "<span class='badge badge-secondary'>{$duration}</span>",
            ];
        });

        return response()->json([
            'draw' => (int) $request->input('draw'),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function devicesDataTable(Request $request, $userId): JsonResponse
    {
        $query = UserDevice::query()->where('user_id', $userId);

        $recordsTotal = (clone $query)->count();

        // Search
        if (!empty($request->input('search')['value'])) {
            $search = $request->input('search')['value'];
            $query->where('device_type', 'ILIKE', "%{$search}%");
        }

        // Filters
        //...

        $recordsFiltered = (clone $query)->count();

        // Order by
        $columns = ['id', 'id', 'id', 'id'];

        $orderColumnIndex = $request->input('order.0.column', 0);
        $orderColumn = $columns[$orderColumnIndex] ?? 'id';
        $orderDir = $request->input('order.0.dir', 'desc');

        $query->orderBy($orderColumn, $orderDir);

        // Pagination
        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 10);
        $query->offset($start)->limit($length > 0 ? $length : PHP_INT_MAX);

        //
        $list = $query->get();

        $data = $list->map(function ($item) {
            $banInfo = $item->is_banned
                ? "<i class='fa fa-ban text-danger' data-bs-toggle='tooltip' data-bs-placement='top' title='Cihaz engellendi'></i>"
                : "";
            $banBtnDataType = $item->is_banned ? 'unban' : 'ban';
            return [
                "{$banInfo} {$item->device_unique_id}",
                $item->device_type,
                $item->device_model,
                view('components.admin.action-buttons', [
                    'itemId' => $item->id,
                    'customButtons' => [
                        [
                            'class' => 'blockUserDeviceBtn',
                            'label' => $item->is_banned ? 'Engeli Kaldır' : 'Engelle',
                            'attrs' => "data-type={$banBtnDataType}"
                        ],
                    ]
                ])->render()
            ];
        });

        return response()->json([
            'draw' => (int) $request->input('draw'),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function deviceBlock(Request $request, $userId, $id): JsonResponse
    {
        $userDevice = UserDevice::with(['user.deviceLogins'])->where('user_id', $userId)->where('id', $id)->first();
        if (!$userDevice) {
            return response()->json([
                'message' => 'Engellenmek istenen cihaz bulunamadı.',
            ], 404);
        }

        $request->validate([
            'type' => 'required|in:ban,unban',
        ], [
            'type.required' => 'Sayfayı yenileyip tekrar deneyiniz.',
            'type.in' => 'Sayfayı yenileyip tekrar deneyiniz.',
        ]);

        $user = $userDevice->user;

        if ($request->input('type') == 'ban') {
            $userDevice->is_banned = true;
            $userDevice->banned_at = now();

            $user = $userDevice->user;
            $user->deviceLogins()->where('device_unique_id', $userDevice->device_unique_id)->get()->each(function ($login) use ($user) {
                $fullToken = $login->access_token;
                $plainToken = explode('|', str_replace('Bearer ', '', $fullToken))[1];
                $hashedToken = hash('sha256', $plainToken);

                $user->tokens()->where('token', $hashedToken)->delete();
            });
        } else {
            $userDevice->is_banned = false;
            $userDevice->banned_at = null;
        }

        $userDevice->save();

        return response()->json([
            'message' => $request->input('type') == 'ban' ? 'Cihaz engellendi.' : 'Cihaz engeli kaldırıldı.',
        ]);
    }

    public function deviceLoginsDataTable(Request $request, $userId): JsonResponse
    {
        $query = UserDeviceLogin::query()->where('user_id', $userId);

        $recordsTotal = (clone $query)->count();

        // Search
        if (!empty($request->input('search')['value'])) {
            $search = $request->input('search')['value'];
            $query->where('device_type', 'ILIKE', "%{$search}%");
        }

        // Filters
        if ($request->filled('device_unique_id')) {
            $query->where('device_unique_id', $request->input('device_unique_id'));
        }

        $recordsFiltered = (clone $query)->count();

        // Order by
        $columns = ['id', 'id', 'id', 'id'];

        $orderColumnIndex = $request->input('order.0.column', 0);
        $orderColumn = $columns[$orderColumnIndex] ?? 'id';
        $orderDir = $request->input('order.0.dir', 'desc');

        $query->orderBy($orderColumn, $orderDir);

        // Pagination
        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 10);
        $query->offset($start)->limit($length > 0 ? $length : PHP_INT_MAX);

        //
        $list = $query->get();

        $data = $list->map(function ($item) {
            return [
                $item->device_unique_id,
                $item->get_last_activity_at,
                $item->device_type,
                $item->device_model,
                $item->device_ip,
                view('components.admin.action-buttons', [
                    'itemId' => $item->id,
                    'customButtons' => [
                        [
                            'class' => 'blockUserDeviceBtn',
                            'label' => 'Engelle'
                        ],
                    ]
                ])->render()
            ];
        });

        return response()->json([
            'draw' => (int) $request->input('draw'),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function accountApprove(Request $request, $userId): JsonResponse
    {
        $user = User::withTrashed()->find($userId);
        if (!$user) {
            throw new NotFoundHttpException();
        }

        $user->update([
            'is_approved' => true,
            'approved_at' => now(),
            'approved_by' => $request->user()->id,
        ]);

        $user->approvalLogs()->create([
            'approved' => true,
            'admin_id' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Hesap onaylandı.'
        ]);
    }

    public function accountReject(Request $request, $userId): JsonResponse
    {
        $user = User::withTrashed()->find($userId);
        if (!$user) {
            throw new NotFoundHttpException();
        }

        $user->update([
            'is_approved' => false,
            'approved_at' => null,
            'approved_by' => null,
        ]);

        $user->approvalLogs()->create([
            'approved' => false,
            'admin_id' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Hesap onayı kaldırıldı.'
        ]);
    }

    public function getPunishmentsByCategory(Request $request, $userId): JsonResponse
    {
        $user = User::withTrashed()->find($userId);
        if (!$user) {
            throw new NotFoundHttpException();
        }

        $request->validate([
            'category_id' => 'required|exists:punishment_categories,id',
        ]);


        $userPunishments = UserPunishment::with('punishment')
            ->where('user_id', $userId)
            ->whereHas('punishment', function ($query) use ($request) {
                $query->where('punishment_category_id', $request->input('category_id'));
            })
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhereDate('expires_at', '>', now());
            })
            ->get();

        $hasActiveRed = $userPunishments->where('punishment.card_type_id', Punishment::RED_CARD)->count() >= 1;
        $hasActiveYellow = $userPunishments->where('punishment.card_type_id', Punishment::YELLOW_CARD)->count() >= 1;


        //mesela burada kullanıcının mevcut awsrı kartı var ise bu cezada kartlardan sadece kırmızı kartı döneriz kırmızı verilebilir ama cezası yok ise tüm kartlarını döneriz
        //kullanıcının zaten mevcut kırmızısı var ise de kart dönmeyiz adam zaten cezalı

        $punishments = Punishment::where('punishment_category_id', $request->input('category_id'))->get();


        return response()->json([
            'user_punishments' => $userPunishments,
            'punishments' => $punishments,
            'has_active_red' => $hasActiveRed,
            'has_active_yellow' => $hasActiveYellow,
        ]);
    }

    public function createPunishment(Request $request, $userId): JsonResponse
    {
        $user = User::withTrashed()->find($userId);
        if (!$user) {
            throw new NotFoundHttpException();
        }

        $request->validate([
            'category_id' => 'required|exists:punishment_categories,id',
            'card_type' => 'required|in:yellow,red,direct_red',
        ]);

        $userPunishments = UserPunishment::with('punishment')
            ->where('user_id', $userId)
            ->whereHas('punishment', function ($query) use ($request) {
                $query->where('punishment_category_id', $request->input('category_id'));
            })
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhereDate('expires_at', '>', now());
            })
            ->get();

        $cardTypeId = $request->input('card_type') == 'yellow' ? Punishment::YELLOW_CARD : Punishment::RED_CARD;
        $punishment = Punishment::where('punishment_category_id', $request->input('category_id'))
            ->when($request->input('card_type') == 'direct_red', function ($query) {
                $query->where('is_direct_punishment', true);
            })
            ->where('card_type_id', $cardTypeId)
            ->first();

        if (!$punishment) {
            return response()->json([
                'message' => 'Ceza sistemde bulunamadı. Sayfayı yenileyip tekrar deneyiniz.',
            ], 404);
        }

        $hasActiveRed = $userPunishments->where('punishment.card_type_id', Punishment::RED_CARD)->count() >= 1;
        $hasActiveYellow = $userPunishments->where('punishment.card_type_id', Punishment::YELLOW_CARD)->count() >= 1;

        if ($request->input('card_type') == 'yellow' && $hasActiveRed) {
            return response()->json([
                'message' => 'Kullanıcı zaten cezalı.',
            ], 404);
        }

        if ($request->input('card_type') == 'red' && !$hasActiveYellow) {
            return response()->json([
                'message' => 'Kullanıcının hiç <b>Sarı Kartı</b> olmadıgı için <b>Kırmızı Kart</b> verilemez. <b>Doğrudan Kırmızı Kart</b> verebilirsiniz.',
            ], 404);
        }

        if (($request->input('card_type') == 'red' || $request->input('card_type') == 'direct_red') && $hasActiveRed) {
            return response()->json([
                'message' => 'Kullanıcı zaten <b>Kırmızı Kart</b> almış.',
            ], 404);
        }

        if ($request->input('card_type') == 'direct_red' && $hasActiveYellow) {
            return response()->json([
                'message' => 'Kullanıcı zaten <b>Sarı Kart</b> almış. Doğrudan <b>Kırmızı Kart</b> vermezsiniz. <b>Kırmızı Kart</b> veriniz.',
            ], 404);
        }


        if ($request->input('card_type') == 'yellow' && $hasActiveYellow) {
            return response()->json([
                'message' => 'Kullanıcı zaten sarı kart almış. Kırmızı kart verebilirsiniz.',
            ], 404);
        }

        $userPunishment = UserPunishment::create([
            'user_id' => $userId,
            'punishment_id' => $punishment->id,
            'applied_at' => now(),
            'expires_at' => $request->input('card_type') != 'yellow' ? Carbon::now()->addDays(7) : null,
        ]);

        $user->notify(new UserPunishedNotification($userPunishment));

        return response()->json([
            'message' => 'Ceza başarıyla verildi.',
        ]);
    }

    public function punishmentsDataTable(Request $request, $userId): JsonResponse
    {
        $query = UserPunishment::with(['punishment.category.parent'])->where('user_id', $userId);

        $recordsTotal = (clone $query)->count();

        // Search
        //...

        // Filters
        //...

        $recordsFiltered = (clone $query)->count();

        // Order by
        $columns = ['id', 'id', 'id', 'id', 'id', 'id'];

        $orderColumnIndex = $request->input('order.0.column');
        $orderColumn = $columns[$orderColumnIndex] ?? 'id';
        $orderDir = $request->input('order.0.dir', 'desc');

        if ($orderColumnIndex === null) {
            $orderColumn = 'id';
            $orderDir = 'desc';
        }

        $query->orderBy($orderColumn, $orderDir);

        // Pagination
        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 10);
        $query->offset($start)->limit($length > 0 ? $length : PHP_INT_MAX);

        //
        $list = $query->get();

        $data = $list->map(function ($item) {
            $category = $item?->punishment?->category?->name ?? 'Bulunamadı';
            $parentCategory = $item?->punishment?->category?->parent?->name ?? 'Bulunamadı';

            return [
                $item->id,
                "<span class='badge badge-secondary'>{$parentCategory} - {$category}</span>",
                $item->punishment?->get_card_type ?? 'Bulunamadı',
                "<span class='badge badge-secondary'>{$item->get_applied_at}</span>",
                "<span class='badge badge-secondary'>{$item->get_expires_at}</span>",
            ];
        });

        return response()->json([
            'draw' => (int) $request->input('draw'),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }
}
