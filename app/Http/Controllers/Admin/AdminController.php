<?php

namespace App\Http\Controllers\Admin;

use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\View\View;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Admin\StoreRequest;
use App\Http\Requests\Admin\Admin\UpdateRequest;
use Hash;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    public function index(): View
    {
        return view('admin.pages.admins.index');
    }

    public function dataTable(Request $request): JsonResponse
    {
        $query = Admin::query()->with('roles');

        $recordsTotal = (clone $query)->count();

        // Search
        if (!empty($request->input('search')['value'])) {
            $search = $request->input('search')['value'];
            $query->where('first_name', 'ILIKE', "%{$search}%")
                ->orWhere("last_name", "LIKE", "%{$search}%")
                ->orWhere("email", "LIKE", "%{$search}%");
        }

        // Filters
        //...


        // Order by
        $columns = ['first_name', 'last_name', 'email'];

        $orderColumnIndex = $request->input('order.0.column');
        $orderColumn = $columns[$orderColumnIndex] ?? 'created_at';
        $orderDir = $request->input('order.0.dir', 'desc');

        if ($orderColumnIndex === null) {
            $orderColumn = 'created_at';
            $orderDir = 'desc';
        }

        $query->orderBy($orderColumn, $orderDir);

        //
        $recordsFiltered = (clone $query)->count();

        // Pagination
        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 10);
        $query->offset($start)->limit($length > 0 ? $length : PHP_INT_MAX);

        //
        $list = $query->get();

        $data = $list->map(function ($item) {
            $roles = $item->roles->pluck('name')->implode(', ');
            return [
                $item->first_name,
                $item->last_name,
                $item->email,
                "<span class='badge badge-light-primary'>{$roles}</span>",
                view('components.admin.action-buttons', [
                    'itemId' => $item->id,
                    'showEdit' => true,
                    'editBtnClass' => 'editBtn',
                    'showDelete' => true,
                    'deleteBtnClass' => 'deleteBtn',
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

    public function store(StoreRequest $request): JsonResponse
    {
        $admin = Admin::create([
            'first_name' => $request->input('first_name'),
            'last_name' => $request->input('last_name'),
            'email' => $request->input('email'),
            'password' => $request->input('password'),
        ]);

        $admin->roles()->sync($request->input('role_ids'));

        return response()->json([
            'message' => 'Kullanıcı oluşturuldu.',
            'data' => $admin,
        ]);
    }

    public function update(UpdateRequest $request, $id): JsonResponse
    {
        $admin = Admin::find($id);
        if (!$admin) {
            return response()->json([
                'error' => 'Kullanıcı bulunamadı.',
            ], 404);
        }

        $data = [
            'first_name' => $request->input('first_name'),
            'last_name' => $request->input('last_name'),
            'email' => $request->input('email'),
        ];

        if ($request->filled('password')) {
            $data['password'] = $request->input('password');
        }

        $admin->update($data);

        $admin->roles()->sync($request->input('role_ids'));

        return response()->json([
            'message' => 'Kullanıcı güncellendi.',
        ]);
    }

    public function getAdmin($id): JsonResponse
    {
        $admin = Admin::find($id);
        if (!$admin) {
            return response()->json([
                'error' => 'Kullanıcı bulunamadı.',
            ], 404);
        }

        $admin->load('roles');

        return response()->json([
            'data' => $admin,
        ]);
    }

    public function delete($id): JsonResponse
    {
        $admin = Admin::find($id);
        if (!$admin) {
            return response()->json([
                'error' => 'Kullanıcı bulunamadı.',
            ], 404);
        }

        $admin->delete();

        return response()->json([
            'message' => 'Kullanıcı silindi.',
        ]);
    }

    public function notifications()
    {
        $admin = Auth::guard('admin')->user();

        $admin->load('notifications');

        $notifications = $admin->notifications()->orderBy('created_at', 'desc')->limit(50)->get();

        $unreadCount = $admin->notifications()->whereNull('read_at')->count();

        return response()->json([
            'data' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }


    public function myProfile()
    {
        $admin = Auth::guard('admin')->user();

        return view('admin.pages.admins.my-profile', compact('admin'));
    }
}
