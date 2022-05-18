<?php

namespace Database\Seeders\Tenant;

use Illuminate\Database\Seeder;
use App\Models\Tenant\Role;

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

        // predefine permission

        // role Manager 

        $submodulePermission = [
            [
                'role_id' => 4,
                'sub_module_permission' => [
                    [
                        'sub_module_id' => 2,
                        'is_list' => true,
                    ],
                    [
                        'sub_module_id' => 3,
                        'is_list' => true,
                        'is_create' => true,
                        'is_edit' => true,
                        'is_delete' => true,
                        'is_view' => true,
                    ],
                    [
                        'sub_module_id' => 14,
                        'is_list' => true,
                        'is_create' => true,
                        'is_edit' => true,
                        'is_delete' => true,
                        'is_view' => true,
                        'is_comment' => true,
                    ],
                    [
                        'sub_module_id' => 9,
                        'is_list' => true,
                        'is_create' => true,
                        'is_edit' => true,
                        'is_delete' => true,
                        'is_view' => true,
                    ],
                    [
                        'sub_module_id' => 15,
                        'is_list' => true,
                        'is_create' => true,
                        'is_edit' => true,
                        'is_delete' => true,
                        'is_view' => true,
                    ],
                    [
                        'sub_module_id' => 16,
                        'is_list' => true,
                        'is_create' => true,
                        'is_edit' => true,
                        'is_delete' => true,
                        'is_view' => true,
                    ],
                    [
                        'sub_module_id' => 17,
                        'is_list' => true,
                        'is_create' => true,
                        'is_edit' => true,
                        'is_delete' => true,
                        'is_view' => true,
                    ],
                    [
                        'sub_module_id' => 18,
                        'is_list' => true,
                        'is_create' => true,
                        'is_edit' => true,
                        'is_delete' => true,
                        'is_view' => true,
                    ],
                    [
                        'sub_module_id' => 19,
                        'is_list' => true,
                        'is_create' => true,
                        'is_edit' => true,
                        'is_delete' => true,
                        'is_view' => true,
                    ],
                    [
                        'sub_module_id' => 20,
                        'is_list' => true,
                        'is_create' => true,
                        'is_edit' => true,
                        'is_delete' => true,
                        'is_view' => true,
                    ],
                    [
                        'sub_module_id' => 10,
                        'is_list' => true,
                        'is_create' => true,
                        'is_edit' => true,
                        'is_delete' => true,
                        'is_view' => true,
                    ],
                    [
                        'sub_module_id' => 11,
                        'is_list' => true,
                        'is_create' => true,
                        'is_edit' => true,
                        'is_delete' => true,
                        'is_view' => true,
                    ],
                    [
                        'sub_module_id' => 12,
                        'is_list' => true,
                        'is_create' => true,
                        'is_edit' => true,
                        'is_delete' => true,
                        'is_view' => true,
                    ],
                    [
                        'sub_module_id' => 13,
                        'is_list' => true,
                        'is_edit' => true,
                        'is_view' => true,
                    ],
                    [
                        'sub_module_id' => 21,
                        'is_list' => true,
                        'is_create' => true,
                        'is_edit' => true,
                        'is_delete' => true,
                        'is_view' => true,
                        'is_approve_reject' => true,
                    ],
                    [
                        'sub_module_id' => 22,
                        'is_list' => true,
                        'is_create' => true,
                        'is_edit' => true,
                        'is_delete' => true,
                        'is_view' => true,
                        'is_assign' => true
                    ],
                    [
                        'sub_module_id' => 23,
                        'is_list' => true,
                        'is_create' => true,
                        'is_edit' => true,
                        'is_delete' => true,
                        'is_view' => true,
                        'is_assign' => true
                    ],
                ]
            ]
        ];
    }
}
