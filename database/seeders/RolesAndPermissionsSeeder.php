<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 创建权限
        $permission1 = Permission::create(['name' => 'view dashboard']);
        $permission2 = Permission::create(['name' => 'edit posts']);

        // 创建角色
        $adminRole = Role::create(['name' => 'admin']);
        $userRole = Role::create(['name' => 'user']);

        // 给角色分配权限
        $adminRole->givePermissionTo($permission1);
        $adminRole->givePermissionTo($permission2);

        $userRole->givePermissionTo($permission1);

        // 创建一个用户并分配角色
        $user = User::find(1); // 假设用户ID为1
        // 如果用户不存在，则创建该用户
        if (!$user) {
            $user = User::create([
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'password' => bcrypt('password'), // 你可以修改密码
            ]);
        }

        $user->assignRole('admin'); // 将 'admin' 角色分配给用户
    }
}
