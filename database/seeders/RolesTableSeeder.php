<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolesTableSeeder extends Seeder
{
    public function run()
    {
        $roles = [
            [
                'id'    => 1,
                'name' => 'Admin',
                'guard_name' => 'api',
            ],
            [
                'id'    => 2,
                'name' => 'User',
                'guard_name' => 'api',
            ],
            
        ];

        Role::insert($roles);
    }
}