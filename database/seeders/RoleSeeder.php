<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run()
    {
        $permissions = [
            'user' => [
                'user list',
            ],
            'video' => [
                'video list',
            ],
            'music' => [
                'music list',
                'music create',
                'music update',
                'music delete',
            ],
            'music category' => [
                'music category list',
                'music category create',
                'music category update',
                'music category delete',
            ],
            'artist' => [
                'artist list',
                'artist create',
                'artist update',
                'artist delete',
            ],
            'story' => [
                'story list',
            ],
            'live stream' => [
                'live stream list',
                'live stream gift list',
            ],
            'live stream category' => [
                'live stream category list',
            ],
            'challenge' => [
                'challenge list',
            ],
            'payment' => [
                'payment list',
                'waiting approval payment list',
            ],
            'gift' => [
                'gift list',
            ],
            'report problem' => [
                'report problem list',
            ],
            'bulk notification' => [
                'send bulk notification',
            ],
            'coupon code' => [
                'coupon code list',
            ],
            'admin' => [
                'admin list',
                'admin create',
                'admin update',
                'admin delete',
            ],
            'role' => [
                'role list',
                'role create',
                'role update',
                'role delete',
            ],
        ];

        foreach ($permissions as $group => $perms) {
            foreach ($perms as $perm) {
                Permission::firstOrCreate([
                    'name' => $perm,
                    'group_name' => $group,
                    'guard_name' => 'admin',
                ]);
            }
        }

        Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'admin']);

        $admin = Admin::where('email', 'info@asiste.com.tr')->first();
        if ($admin) {
            $admin->syncRoles(['Super Admin']);
        }
    }
}
