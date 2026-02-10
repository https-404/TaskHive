<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::query()->firstOrCreate(
            ['id' => 1],
            [
                'name' => 'user',
                'description' => 'Default user role',
            ]
        );
    }
}
