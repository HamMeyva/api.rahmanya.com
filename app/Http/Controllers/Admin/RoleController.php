<?php

namespace App\Http\Controllers\Admin;

use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\RedirectResponse;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    public function index(): View
    {
        $roles = Role::with('permissions')
            ->orderBy('id', 'asc')
            ->get();

        return view('admin.pages.roles.index', compact('roles'));
    }

    public function create(Request $request): View
    {
        $permissionGroups = Permission::get()->groupBy('group_name');
        $title = "Rol Oluştur";
        $submitUrl = route('admin.roles.store');

        return view('admin.pages.roles.create-edit', compact('permissionGroups', 'submitUrl', 'title'));
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'permission' => 'required|array',
            'permission.*' => 'required|exists:permissions,id',
        ]);

        $validPermissionIds = Permission::query()
            ->where('guard_name', 'admin')
            ->whereIn('id', $request->input('permission'))
            ->pluck('id')
            ->toArray();

        $role = Role::create(['name' => $request->input('name')]);
        $role->permissions()->sync($validPermissionIds);

        return response()->json([
            'message' => 'Rol başarıyla oluşturuldu.',
            'redirect_url' => route('admin.roles.edit', ['role' => $role->id])
        ]);
    }

    public function edit(Request $request, Role $role): RedirectResponse|View
    {
        if ($role->name === 'Super Admin') {
            return redirect()->back();
        }
        $permissionGroups = Permission::get()->groupBy('group_name');
        $title = $role->name;
        $submitUrl = route('admin.roles.update', ['role' => $role]);

        return view('admin.pages.roles.create-edit', compact('role', 'permissionGroups', 'submitUrl', 'title'));
    }

    public function update(Request $request, Role $role): JsonResponse
    {
        if ($role->name === 'Super Admin') {
            return response()->json([
                'message' => 'Super Admin rolü düzenlenemez.',
            ], 404);
        }
        $request->validate([
            'name' => "required|string|max:255|unique:roles,name,{$role->id}",
            'permission' => 'required|array',
            'permission.*' => 'required|exists:permissions,id',
        ]);

        $validPermissionIds = Permission::query()
            ->where('guard_name', 'admin')
            ->whereIn('id', $request->input('permission'))
            ->pluck('id')
            ->toArray();

        $role->name = $request->input('name');
        $role->save();

        $role->permissions()->sync($validPermissionIds);

        return response()->json([
            'message' => 'Rol başarıyla düzenlendi.',
            'redirect_url' => route('admin.roles.edit', ['role' => $role->id])
        ]);
    }

    public function delete(Request $request, Role $role): JsonResponse
    {
        if ($role->name === 'Super Admin') {
            return response()->json([
                'message' => 'Super Admin rolü silinemez.',
            ], 404);
        }

        $userCount = $role->users()->count();
        if ($userCount > 0) {
            return response()->json([
                'message' => "Bu rolü silemezsiniz. Bu rol {$userCount} adet kullanıcının rolüdür.",
            ], 404);
        }

        $role->delete();

        return response()->json([
            'message' => 'Rol başarıyla silindi.',
        ]);
    }
}
