<?php

namespace Database\Seeders\Tenant;

use Illuminate\Database\Seeder;
use App\Models\Tenant\RoleHasSubModule;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        // predefine permission

        // Role => Manager

        $managerSubmodulePermission = [
            [
                'role_id' => 4,
                'sub_module_permission' => [
                    [
                        //module name => Authorization
                        'sub_module_id' => 2,
                        'is_list' => true,
                    ],
                    [
                        //module name => General
                        'sub_module_id' => 3,
                        'is_list' => true,
                        'is_create' => true,
                        'is_edit' => true,
                        'is_delete' => true,
                        'is_view' => true,
                    ],
                    [
                        //module name => Planning and Scheduling
                        'sub_module_id' => 14,
                        'is_list' => true,
                        'is_create' => true,
                        'is_edit' => true,
                        'is_delete' => true,
                        'is_view' => true,
                        'is_comment' => true,
                    ],
                    [
                        //module name => Planning and Scheduling
                        'sub_module_id' => 9,
                        'is_list' => true,
                        'is_create' => true,
                        'is_edit' => true,
                        'is_delete' => true,
                        'is_view' => true,
                    ],
                    [
                        //module name => Planning and Scheduling
                        'sub_module_id' => 15,
                        'is_list' => true,
                        'is_create' => true,
                        'is_edit' => true,
                        'is_delete' => true,
                        'is_view' => true,
                    ],
                    [
                        //module name => Planning and Scheduling
                        'sub_module_id' => 16,
                        'is_list' => true,
                        'is_create' => true,
                        'is_edit' => true,
                        'is_delete' => true,
                        'is_view' => true,
                    ],
                    [
                        //module name => Planning and Scheduling
                        'sub_module_id' => 17,
                        'is_list' => true,
                        'is_create' => true,
                        'is_edit' => true,
                        'is_delete' => true,
                        'is_view' => true,
                    ],
                    [
                        //module name => Planning and Scheduling
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
                        //module name => Planning and Scheduling
                        'sub_module_id' => 20,
                        'is_list' => true,
                        'is_create' => true,
                        'is_edit' => true,
                        'is_delete' => true,
                        'is_view' => true,
                    ],
                    [
                        //module name => Planning and Scheduling
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
                        //module name => Planning and Scheduling
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
                        //module name => Planning and Scheduling
                        'sub_module_id' => 21,
                        'is_list' => true,
                        'is_create' => true,
                        'is_edit' => true,
                        'is_delete' => true,
                        'is_view' => true,
                        'is_approve_reject' => true,
                    ],
                    [
                        //module name => Planning and Scheduling
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
                    [
                        //module name => Planning and Scheduling
                        'sub_module_id' => 24,
                        'is_list' => true,
                        'is_create' => true,
                        'is_edit' => true,
                        'is_delete' => true,
                        'is_view' => true
                    ],
                    [
                        //module name => Planning and Scheduling
                        'sub_module_id' => 25,
                        'is_list' => true,
                        'is_create' => true,
                        'is_edit' => true,
                        'is_delete' => true,
                        'is_view' => true
                    ],
                    [
                        //module name => Planning and Scheduling
                        'sub_module_id' => 26,
                        'is_list' => true,
                        'is_create' => true,
                        'is_edit' => true,
                        'is_delete' => true,
                        'is_view' => true
                    ],
                    [
                        //module name => Qa/Qc
                        'sub_module_id' => 27,
                        'is_list' => true,
                        'is_create' => true,
                        'is_edit' => true,
                        'is_delete' => true,
                        'is_view' => true
                    ],
                    [
                        //module name => Qa/Qc
                        'sub_module_id' => 28,
                        'is_list' => true,
                        'is_create' => true,
                        'is_edit' => true,
                        'is_delete' => true,
                        'is_view' => true,
                        'is_assign' => true,
                        'is_approve_reject' => true,
                    ],
                    [
                        //module name => Qs
                        'sub_module_id' => 29,
                        'is_list' => true,
                        'is_create' => true,
                        'is_edit' => true,
                        'is_delete' => true,
                        'is_view' => true,
                        'is_approve_reject' => true,
                    ],
                    [
                        //module name => Design Team
                        'sub_module_id' => 32,
                        'is_list' => true,
                        'is_create' => true,
                        'is_edit' => true,
                        'is_delete' => true,
                        'is_view' => true,
                        'is_assign' => true
                    ],
                    [
                        //module name => HSE
                        'sub_module_id' => 34,
                        'is_list' => true,
                        'is_create' => true,
                        'is_edit' => true,
                        'is_delete' => true,
                        'is_view' => true,
                        'is_assign' => true
                    ],
                    [
                        //module name => HSE
                        'sub_module_id' => 35,
                        'is_list' => true,
                        'is_create' => true,
                        'is_edit' => true,
                        'is_delete' => true,
                        'is_view' => true
                    ],
                ]
            ]
        ];

        foreach ($managerSubmodulePermission as $managerValue) {
            if (isset($managerValue) && !empty($managerValue)) {
                foreach ($managerValue['sub_module_permission'] as $value) {
                    $submodulePermission = new RoleHasSubModule();
                    $submodulePermission->role_id = $managerValue['role_id'];
                    $submodulePermission->sub_module_id = $value['sub_module_id'];
                    $submodulePermission->is_list = isset($value['is_list']) ? $value['is_list'] : false;
                    $submodulePermission->is_create = isset($value['is_create']) ? $value['is_create'] : false;
                    $submodulePermission->is_edit = isset($value['is_edit']) ? $value['is_edit'] : false;
                    $submodulePermission->is_delete = isset($value['is_delete']) ? $value['is_delete'] : false;
                    $submodulePermission->is_view = isset($value['is_view']) ? $value['is_view'] : false;
                    $submodulePermission->is_comment = isset($value['is_comment']) ? $value['is_comment'] : false;
                    $submodulePermission->is_assign = isset($value['is_assign']) ? $value['is_assign'] : false;
                    $submodulePermission->is_approve_reject = isset($value['is_approve_reject']) ? $value['is_approve_reject'] : false;
                    $submodulePermission->save();
                }
            }
        }

        // Role => Project Engineer

        $projectEngineerSubmodulePermission = [
            [
                'role_id' => 5,
                'sub_module_permission' => [
                    [
                        //module name => Authorization
                        'sub_module_id' => 2,
                        'is_list' => true,
                    ],
                    [
                        //module name => General
                        'sub_module_id' => 3,
                        'is_list' => true,
                        'is_view' => true
                    ],
                    [
                        //module name => Planning and Scheduling
                        'sub_module_id' => 14,
                        'is_list' => true,
                        'is_create' => true,
                        'is_edit' => true,
                        'is_delete' => true,
                        'is_view' => true,
                        'is_comment' => true,
                    ],
                    [
                        //module name => Planning and Scheduling
                        'sub_module_id' => 9,
                        'is_list' => true,
                        'is_create' => true,
                        'is_edit' => true,
                        'is_delete' => true,
                        'is_view' => true,
                    ],
                    [
                        //module name => Planning and Scheduling
                        'sub_module_id' => 15,
                        'is_list' => true,
                        'is_create' => true,
                        'is_edit' => true,
                        'is_delete' => true,
                        'is_view' => true,
                    ],
                    [
                        //module name => Planning and Scheduling
                        'sub_module_id' => 16,
                        'is_list' => true,
                        'is_create' => true,
                        'is_edit' => true,
                        'is_delete' => true,
                        'is_view' => true,
                    ],
                    [
                        //module name => Planning and Scheduling
                        'sub_module_id' => 17,
                        'is_list' => true,
                        'is_create' => true,
                        'is_edit' => true,
                        'is_delete' => true,
                        'is_view' => true,
                    ],
                    [
                        //module name => Planning and Scheduling
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
                        //module name => Planning and Scheduling
                        'sub_module_id' => 20,
                        'is_list' => true,
                        'is_create' => true,
                        'is_edit' => true,
                        'is_delete' => true,
                        'is_view' => true,
                    ],
                    [
                        //module name => Planning and Scheduling
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
                        //module name => Planning and Scheduling
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
                        //module name => Planning and Scheduling
                        'sub_module_id' => 21,
                        'is_list' => true,
                        'is_create' => true,
                        'is_edit' => true,
                        'is_delete' => true,
                        'is_view' => true,
                        'is_approve_reject' => false,
                    ],
                    [
                        //module name => Planning and Scheduling
                        'sub_module_id' => 22,
                        'is_list' => true,
                        'is_create' => true,
                        'is_edit' => true,
                        'is_delete' => true,
                        'is_view' => true,
                        'is_assign' => false
                    ],
                    [
                        'sub_module_id' => 23,
                        'is_list' => true,
                        'is_create' => true,
                        'is_edit' => true,
                        'is_delete' => true,
                        'is_view' => true,
                        'is_assign' => false
                    ],
                    [
                        //module name => Planning and Scheduling
                        'sub_module_id' => 24,
                        'is_list' => true,
                        'is_create' => true,
                        'is_edit' => true,
                        'is_delete' => true,
                        'is_view' => true
                    ],
                    [
                        //module name => Design Team
                        'sub_module_id' => 32,
                        'is_list' => true,
                        'is_create' => true,
                        'is_edit' => true,
                        'is_delete' => true,
                        'is_view' => true,
                        'is_assign' => false
                    ],
                ]
            ]
        ];

        foreach ($projectEngineerSubmodulePermission as $projectEngineerValue) {
            if (isset($projectEngineerValue) && !empty($projectEngineerValue)) {
                foreach ($projectEngineerValue['sub_module_permission'] as $subModuleValue) {
                    $submodulePermission = new RoleHasSubModule();
                    $submodulePermission->role_id = $projectEngineerValue['role_id'];
                    $submodulePermission->sub_module_id = $subModuleValue['sub_module_id'];
                    $submodulePermission->is_list = isset($subModuleValue['is_list']) ? $subModuleValue['is_list'] : false;
                    $submodulePermission->is_create = isset($subModuleValue['is_create']) ? $subModuleValue['is_create'] : false;
                    $submodulePermission->is_edit = isset($subModuleValue['is_edit']) ? $subModuleValue['is_edit'] : false;
                    $submodulePermission->is_delete = isset($subModuleValue['is_delete']) ? $subModuleValue['is_delete'] : false;
                    $submodulePermission->is_view = isset($subModuleValue['is_view']) ? $subModuleValue['is_view'] : false;
                    $submodulePermission->is_comment = isset($subModuleValue['is_comment']) ? $subModuleValue['is_comment'] : false;
                    $submodulePermission->is_assign = isset($subModuleValue['is_assign']) ? $subModuleValue['is_assign'] : false;
                    $submodulePermission->is_approve_reject = isset($subModuleValue['is_approve_reject']) ? $subModuleValue['is_approve_reject'] : false;
                    $submodulePermission->save();
                }
            }
        }

        // Role => QS Department

        $qsDepartmentSubmodulePermission = [
            [
                'role_id' => 6,
                'sub_module_permission' => [
                    [
                        //module name => General
                        'sub_module_id' => 3,
                        'is_list' => true,
                        'is_view' => true
                    ],
                    [
                        //module name => Planning and Scheduling
                        'sub_module_id' => 14,
                        'is_list' => true,
                        'is_create' => false,
                        'is_edit' => false,
                        'is_delete' => false,
                        'is_view' => true,
                        'is_comment' => false,
                    ],
                    [
                        //module name => Qa/Qc
                        'sub_module_id' => 27,
                        'is_list' => true,
                        'is_create' => true,
                        'is_edit' => true,
                        'is_delete' => true,
                        'is_view' => true
                    ],
                    [
                        //module name => Qa/Qc
                        'sub_module_id' => 28,
                        'is_list' => true,
                        'is_create' => true,
                        'is_edit' => true,
                        'is_delete' => true,
                        'is_view' => true,
                        'is_assign' => false,
                        'is_approve_reject' => false
                    ],
                    [
                        //module name => Qs
                        'sub_module_id' => 29,
                        'is_list' => true,
                        'is_create' => true,
                        'is_edit' => true,
                        'is_delete' => true,
                        'is_view' => true,
                        'is_approve_reject' => false,
                    ],
                    [
                        //module name => Qs
                        'sub_module_id' => 30,
                        'is_list' => true,
                        'is_create' => true,
                        'is_edit' => true,
                        'is_delete' => true,
                        'is_view' => true,
                        'is_approve_reject' => false,
                    ],
                    [
                        //module name => Qs
                        'sub_module_id' => 31,
                        'is_list' => true,
                        'is_create' => true,
                        'is_edit' => true,
                        'is_delete' => true,
                        'is_view' => true
                    ],
                ]
            ]
        ];

        foreach ($qsDepartmentSubmodulePermission as $qsDepartmentValue) {
            if (isset($qsDepartmentValue) && !empty($qsDepartmentValue)) {
                foreach ($qsDepartmentValue['sub_module_permission'] as $qsValue) {
                    $submodulePermission = new RoleHasSubModule();
                    $submodulePermission->role_id = $qsDepartmentValue['role_id'];
                    $submodulePermission->sub_module_id = $qsValue['sub_module_id'];
                    $submodulePermission->is_list = isset($qsValue['is_list']) ? $qsValue['is_list'] : false;
                    $submodulePermission->is_create = isset($qsValue['is_create']) ? $qsValue['is_create'] : false;
                    $submodulePermission->is_edit = isset($qsValue['is_edit']) ? $qsValue['is_edit'] : false;
                    $submodulePermission->is_delete = isset($qsValue['is_delete']) ? $qsValue['is_delete'] : false;
                    $submodulePermission->is_view = isset($qsValue['is_view']) ? $qsValue['is_view'] : false;
                    $submodulePermission->is_comment = isset($qsValue['is_comment']) ? $qsValue['is_comment'] : false;
                    $submodulePermission->is_assign = isset($qsValue['is_assign']) ? $qsValue['is_assign'] : false;
                    $submodulePermission->is_approve_reject = isset($qsValue['is_approve_reject']) ? $qsValue['is_approve_reject'] : false;
                    $submodulePermission->save();
                }
            }
        }

        // Role => HSE Department

        $hseDepartmentSubmodulePermission = [
            [
                'role_id' => 7,
                'sub_module_permission' => [
                    [
                        //module name => General
                        'sub_module_id' => 3,
                        'is_list' => true,
                        'is_create' => false,
                        'is_edit' => false,
                        'is_delete' => false,
                        'is_view' => true,
                    ],
                    [
                        //module name => Planning and Scheduling
                        'sub_module_id' => 14,
                        'is_list' => true,
                        'is_create' => false,
                        'is_edit' => false,
                        'is_delete' => false,
                        'is_view' => true,
                        'is_comment' => false,
                    ],
                    [
                        //module name => HSE
                        'sub_module_id' => 33,
                        'is_list' => true,
                        'is_create' => true,
                        'is_edit' => true,
                        'is_delete' => true,
                        'is_view' => true
                    ],
                    [
                        //module name => HSE
                        'sub_module_id' => 34,
                        'is_list' => true,
                        'is_create' => true,
                        'is_edit' => true,
                        'is_delete' => true,
                        'is_view' => true,
                        'is_assign' => false,
                    ],
                    [
                        //module name => HSE
                        'sub_module_id' => 35,
                        'is_list' => true,
                        'is_create' => true,
                        'is_edit' => true,
                        'is_delete' => true,
                        'is_view' => true
                    ],
                ]
            ]
        ];

        foreach ($hseDepartmentSubmodulePermission as $hseDepartmentValue) {
            if (isset($hseDepartmentValue) && !empty($hseDepartmentValue)) {
                foreach ($hseDepartmentValue['sub_module_permission'] as $hseValue) {
                    $submodulePermission = new RoleHasSubModule();
                    $submodulePermission->role_id = $hseDepartmentValue['role_id'];
                    $submodulePermission->sub_module_id = $hseValue['sub_module_id'];
                    $submodulePermission->is_list = isset($hseValue['is_list']) ? $hseValue['is_list'] : false;
                    $submodulePermission->is_create = isset($hseValue['is_create']) ? $hseValue['is_create'] : false;
                    $submodulePermission->is_edit = isset($hseValue['is_edit']) ? $hseValue['is_edit'] : false;
                    $submodulePermission->is_delete = isset($hseValue['is_delete']) ? $hseValue['is_delete'] : false;
                    $submodulePermission->is_view = isset($hseValue['is_view']) ? $hseValue['is_view'] : false;
                    $submodulePermission->is_comment = isset($hseValue['is_comment']) ? $hseValue['is_comment'] : false;
                    $submodulePermission->is_assign = isset($hseValue['is_assign']) ? $hseValue['is_assign'] : false;
                    $submodulePermission->is_approve_reject = isset($hseValue['is_approve_reject']) ? $hseValue['is_approve_reject'] : false;
                    $submodulePermission->save();
                }
            }
        }

        // Role => Design Department

        $designDepartmentSubmodulePermission = [
            [
                'role_id' => 8,
                'sub_module_permission' => [
                    [
                        //module name => General
                        'sub_module_id' => 3,
                        'is_list' => true,
                        'is_view' => true
                    ],
                    [
                        //module name => Planning and Scheduling
                        'sub_module_id' => 14,
                        'is_list' => true,
                        'is_create' => false,
                        'is_edit' => false,
                        'is_delete' => false,
                        'is_view' => true,
                        'is_comment' => false,
                    ],
                    [
                        //module name => Design Team
                        'sub_module_id' => 32,
                        'is_list' => true,
                        'is_create' => false,
                        'is_edit' => false,
                        'is_delete' => false,
                        'is_view' => true,
                        'is_assign' => false,
                    ],
                ]
            ]
        ];

        foreach ($designDepartmentSubmodulePermission as $designDepartmentValue) {
            if (isset($designDepartmentValue) && !empty($designDepartmentValue)) {
                foreach ($designDepartmentValue['sub_module_permission'] as $designValue) {
                    $submodulePermission = new RoleHasSubModule();
                    $submodulePermission->role_id = $designDepartmentValue['role_id'];
                    $submodulePermission->sub_module_id = $designValue['sub_module_id'];
                    $submodulePermission->is_list = isset($designValue['is_list']) ? $designValue['is_list'] : false;
                    $submodulePermission->is_create = isset($designValue['is_create']) ? $designValue['is_create'] : false;
                    $submodulePermission->is_edit = isset($designValue['is_edit']) ? $designValue['is_edit'] : false;
                    $submodulePermission->is_delete = isset($designValue['is_delete']) ? $designValue['is_delete'] : false;
                    $submodulePermission->is_view = isset($designValue['is_view']) ? $designValue['is_view'] : false;
                    $submodulePermission->is_comment = isset($designValue['is_comment']) ? $designValue['is_comment'] : false;
                    $submodulePermission->is_assign = isset($designValue['is_assign']) ? $designValue['is_assign'] : false;
                    $submodulePermission->is_approve_reject = isset($designValue['is_approve_reject']) ? $designValue['is_approve_reject'] : false;
                    $submodulePermission->save();
                }
            }
        }

        // Role => Planner Engineer

        $plannerEngineerSubmodulePermission = [
            [
                'role_id' => 9,
                'sub_module_permission' => [
                    [
                        //module name => General
                        'sub_module_id' => 3,
                        'is_list' => true,
                        'is_view' => true
                    ],
                    [
                        //module name => Planning and Scheduling
                        'sub_module_id' => 14,
                        'is_list' => true,
                        'is_create' => true,
                        'is_edit' => true,
                        'is_delete' => true,
                        'is_view' => true,
                        'is_comment' => true,
                    ],
                    [
                        //module name => Planning and Scheduling
                        'sub_module_id' => 20,
                        'is_list' => true,
                        'is_create' => true,
                        'is_edit' => true,
                        'is_delete' => true,
                        'is_view' => true,
                    ],
                    [
                        //module name => Planning and Scheduling
                        'sub_module_id' => 24,
                        'is_list' => true,
                        'is_create' => true,
                        'is_edit' => true,
                        'is_delete' => true,
                        'is_view' => true,
                    ],
                ]
            ]
        ];

        foreach ($plannerEngineerSubmodulePermission as $plannerEngineerValue) {
            if (isset($plannerEngineerValue) && !empty($plannerEngineerValue)) {
                foreach ($plannerEngineerValue['sub_module_permission'] as $plannerValue) {
                    $submodulePermission = new RoleHasSubModule();
                    $submodulePermission->role_id = $plannerEngineerValue['role_id'];
                    $submodulePermission->sub_module_id = $plannerValue['sub_module_id'];
                    $submodulePermission->is_list = isset($plannerValue['is_list']) ? $plannerValue['is_list'] : false;
                    $submodulePermission->is_create = isset($plannerValue['is_create']) ? $plannerValue['is_create'] : false;
                    $submodulePermission->is_edit = isset($plannerValue['is_edit']) ? $plannerValue['is_edit'] : false;
                    $submodulePermission->is_delete = isset($plannerValue['is_delete']) ? $plannerValue['is_delete'] : false;
                    $submodulePermission->is_view = isset($plannerValue['is_view']) ? $plannerValue['is_view'] : false;
                    $submodulePermission->is_comment = isset($plannerValue['is_comment']) ? $plannerValue['is_comment'] : false;
                    $submodulePermission->is_assign = isset($plannerValue['is_assign']) ? $plannerValue['is_assign'] : false;
                    $submodulePermission->is_approve_reject = isset($plannerValue['is_approve_reject']) ? $plannerValue['is_approve_reject'] : false;
                    $submodulePermission->save();
                }
            }
        }

        // Role => Engineer

        $engineerSubmodulePermission = [
            [
                'role_id' => 10,
                'sub_module_permission' => [
                    [
                        //module name => Authorization
                        'sub_module_id' => 2,
                        'is_list' => true
                    ],
                    [
                        //module name => General
                        'sub_module_id' => 3,
                        'is_list' => true,
                        'is_view' => true
                    ],
                    [
                        //module name => Planning and Scheduling
                        'sub_module_id' => 14,
                        'is_list' => true,
                        'is_create' => true,
                        'is_edit' => true,
                        'is_delete' => false,
                        'is_view' => true,
                        'is_comment' => false,
                    ],
                    [
                        //module name => Planning and Scheduling
                        'sub_module_id' => 22,
                        'is_list' => true,
                        'is_create' => true,
                        'is_edit' => true,
                        'is_delete' => true,
                        'is_view' => true,
                        'is_assign' => false,
                    ],
                    [
                        //module name => Planning and Scheduling
                        'sub_module_id' => 23,
                        'is_list' => true,
                        'is_create' => true,
                        'is_edit' => true,
                        'is_delete' => true,
                        'is_view' => true,
                        'is_assign' => false,
                    ],
                    [
                        //module name => Planning and Scheduling
                        'sub_module_id' => 24,
                        'is_list' => true,
                        'is_create' => true,
                        'is_edit' => true,
                        'is_delete' => true,
                        'is_view' => true
                    ],
                ]
            ]
        ];

        foreach ($engineerSubmodulePermission as $engineerValue) {
            if (isset($engineerValue) && !empty($engineerValue)) {
                foreach ($engineerValue['sub_module_permission'] as $engineerSubModule) {
                    $submodulePermission = new RoleHasSubModule();
                    $submodulePermission->role_id = $engineerValue['role_id'];
                    $submodulePermission->sub_module_id = $engineerSubModule['sub_module_id'];
                    $submodulePermission->is_list = isset($engineerSubModule['is_list']) ? $engineerSubModule['is_list'] : false;
                    $submodulePermission->is_create = isset($engineerSubModule['is_create']) ? $engineerSubModule['is_create'] : false;
                    $submodulePermission->is_edit = isset($engineerSubModule['is_edit']) ? $engineerSubModule['is_edit'] : false;
                    $submodulePermission->is_delete = isset($engineerSubModule['is_delete']) ? $engineerSubModule['is_delete'] : false;
                    $submodulePermission->is_view = isset($engineerSubModule['is_view']) ? $engineerSubModule['is_view'] : false;
                    $submodulePermission->is_comment = isset($engineerSubModule['is_comment']) ? $engineerSubModule['is_comment'] : false;
                    $submodulePermission->is_assign = isset($engineerSubModule['is_assign']) ? $engineerSubModule['is_assign'] : false;
                    $submodulePermission->is_approve_reject = isset($engineerSubModule['is_approve_reject']) ? $engineerSubModule['is_approve_reject'] : false;
                    $submodulePermission->save();
                }
            }
        }

        //  Role => Storekeeper

        $storekeeperSubmodulePermission = [
            [
                'role_id' => 13,
                'sub_module_permission' => [
                    [
                        //module name => General
                        'sub_module_id' => 3,
                        'is_list' => true,
                        'is_view' => true
                    ],
                    [
                        //module name => Masters
                        'sub_module_id' => 4,
                        'is_list' => true,
                        'is_create' => true,
                        'is_edit' => true,
                        'is_delete' => true,
                        'is_view' => true,
                    ],
                    [
                        //module name => Masters
                        'sub_module_id' => 5,
                        'is_list' => true,
                        'is_create' => true,
                        'is_edit' => true,
                        'is_delete' => true,
                        'is_view' => true,
                    ],
                    [
                        //module name => Masters
                        'sub_module_id' => 6,
                        'is_list' => true,
                        'is_create' => true,
                        'is_edit' => true,
                        'is_delete' => true,
                        'is_view' => true,
                    ],
                    [
                        //module name => Masters
                        'sub_module_id' => 7,
                        'is_list' => true,
                        'is_create' => true,
                        'is_edit' => true,
                        'is_delete' => true,
                        'is_view' => true,
                    ],
                    [
                        //module name => Masters
                        'sub_module_id' => 8,
                        'is_list' => true,
                        'is_create' => true,
                        'is_edit' => true,
                        'is_delete' => true,
                        'is_view' => true,
                    ],
                    [
                        //module name => Planning and Scheduling
                        'sub_module_id' => 17,
                        'is_list' => true,
                        'is_create' => true,
                        'is_edit' => true,
                        'is_delete' => true,
                        'is_view' => true,
                    ],
                    [
                        //module name => Planning and Scheduling
                        'sub_module_id' => 11,
                        'is_list' => true,
                        'is_create' => true,
                        'is_edit' => true,
                        'is_delete' => true,
                        'is_view' => true,
                    ],
                    [
                        //module name => Planning and Scheduling
                        'sub_module_id' => 13,
                        'is_list' => true,
                        'is_edit' => true,
                        'is_view' => true
                    ],
                    [
                        //module name => Planning and Scheduling
                        'sub_module_id' => 21,
                        'is_list' => true,
                        'is_create' => true,
                        'is_edit' => true,
                        'is_delete' => true,
                        'is_view' => true,
                        'is_approve_reject' => false,
                    ],
                ]
            ]
        ];

        foreach ($storekeeperSubmodulePermission as $storekeeperSubmoduleValue) {
            if (isset($storekeeperSubmoduleValue) && !empty($storekeeperSubmoduleValue)) {
                foreach ($storekeeperSubmoduleValue['sub_module_permission'] as $storekeeperValue) {
                    $submodulePermission = new RoleHasSubModule();
                    $submodulePermission->role_id = $storekeeperSubmoduleValue['role_id'];
                    $submodulePermission->sub_module_id = $storekeeperValue['sub_module_id'];
                    $submodulePermission->is_list = isset($storekeeperValue['is_list']) ? $storekeeperValue['is_list'] : false;
                    $submodulePermission->is_create = isset($storekeeperValue['is_create']) ? $storekeeperValue['is_create'] : false;
                    $submodulePermission->is_edit = isset($storekeeperValue['is_edit']) ? $storekeeperValue['is_edit'] : false;
                    $submodulePermission->is_delete = isset($storekeeperValue['is_delete']) ? $storekeeperValue['is_delete'] : false;
                    $submodulePermission->is_view = isset($storekeeperValue['is_view']) ? $storekeeperValue['is_view'] : false;
                    $submodulePermission->is_comment = isset($storekeeperValue['is_comment']) ? $storekeeperValue['is_comment'] : false;
                    $submodulePermission->is_assign = isset($storekeeperValue['is_assign']) ? $storekeeperValue['is_assign'] : false;
                    $submodulePermission->is_approve_reject = isset($storekeeperValue['is_approve_reject']) ? $storekeeperValue['is_approve_reject'] : false;
                    $submodulePermission->save();
                }
            }
        }

        // Role => Timekeeper

        $timekeeperSubmodulePermission = [
            [
                'role_id' => 14,
                'sub_module_permission' => [
                    [
                        //module name => General
                        'sub_module_id' => 3,
                        'is_list' => true,
                        'is_view' => true
                    ],
                    [
                        //module name => Planning and Scheduling
                        'sub_module_id' => 14,
                        'is_list' => true,
                        'is_create' => false,
                        'is_edit' => false,
                        'is_delete' => false,
                        'is_view' => true,
                        'is_comment' => false,
                    ],
                    [
                        //module name => Planning and Scheduling
                        'sub_module_id' => 19,
                        'is_list' => true,
                        'is_create' => true,
                        'is_edit' => true,
                        'is_delete' => true,
                        'is_view' => true,
                    ],
                    [
                        //module name => Planning and Scheduling
                        'sub_module_id' => 12,
                        'is_list' => true,
                        'is_create' => true,
                        'is_edit' => true,
                        'is_delete' => true,
                        'is_view' => true,
                    ],
                    [
                        //module name => Planning and Scheduling
                        'sub_module_id' => 22,
                        'is_list' => true,
                        'is_create' => true,
                        'is_edit' => true,
                        'is_delete' => true,
                        'is_view' => true,
                        'is_assign' => false,
                    ],
                ]
            ]
        ];

        foreach ($timekeeperSubmodulePermission as $timekeeperSubmoduleValue) {
            if (isset($timekeeperSubmoduleValue) && !empty($timekeeperSubmoduleValue)) {
                foreach ($timekeeperSubmoduleValue['sub_module_permission'] as $timekeeperValue) {
                    $submodulePermission = new RoleHasSubModule();
                    $submodulePermission->role_id = $timekeeperSubmoduleValue['role_id'];
                    $submodulePermission->sub_module_id = $timekeeperValue['sub_module_id'];
                    $submodulePermission->is_list = isset($timekeeperValue['is_list']) ? $timekeeperValue['is_list'] : false;
                    $submodulePermission->is_create = isset($timekeeperValue['is_create']) ? $timekeeperValue['is_create'] : false;
                    $submodulePermission->is_edit = isset($timekeeperValue['is_edit']) ? $timekeeperValue['is_edit'] : false;
                    $submodulePermission->is_delete = isset($timekeeperValue['is_delete']) ? $timekeeperValue['is_delete'] : false;
                    $submodulePermission->is_view = isset($timekeeperValue['is_view']) ? $timekeeperValue['is_view'] : false;
                    $submodulePermission->is_comment = isset($timekeeperValue['is_comment']) ? $timekeeperValue['is_comment'] : false;
                    $submodulePermission->is_assign = isset($timekeeperValue['is_assign']) ? $timekeeperValue['is_assign'] : false;
                    $submodulePermission->is_approve_reject = isset($timekeeperValue['is_approve_reject']) ? $timekeeperValue['is_approve_reject'] : false;
                    $submodulePermission->save();
                }
            }
        }
    }
}
