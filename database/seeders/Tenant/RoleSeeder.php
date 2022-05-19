<?php

namespace Database\Seeders\Tenant;

use Illuminate\Database\Seeder;
use App\Models\Tenant\Role;
use App\Models\Tenant\RoleHasSubModule;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $roles = [
            ['name' => 'Super Admin'],
            ['name' => 'Company Admin'],
            ['name' => 'Construction Site Admin'],
            ['name' => 'Manager'],
            ['name' => 'Project Engineer'],
            ['name' => 'QS Department'],
            ['name' => 'HSE Department'],
            ['name' => 'Design Department'],
            ['name' => 'Planner Engineer'],
            ['name' => 'Engineer'],
            ['name' => 'Foreman'],
            ['name' => 'QA/QC'],
            ['name' => 'Storekeeper'],
            ['name' => 'Timekeeper'],
        ];

        foreach ($roles as $rKey => $rVal) {
            $role = new Role();
            $role->name = $rVal['name'];
            $role->save();
        }
    }
}
