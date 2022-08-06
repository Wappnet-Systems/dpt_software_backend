<?php

namespace Database\Seeders\System;

use Illuminate\Database\Seeder;
use App\Models\System\Module;
use App\Models\System\SubModule;

class ModuleSubModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $modules = [
            [
                'name' => 'Authorization',
                'sub_module' => [
                    ['name' => 'Role Management'],
                    ['name' => 'User Management'],
                ]
            ],
            [
                'name' => 'General',
                'sub_module' => [
                    ['name' => 'Project Management'],
                ]
            ],
            [
                'name' => 'Masters',
                'sub_module' => [
                    ['name' => 'Unit Type Management'],
                    ['name' => 'Material Type Management'],
                    ['name' => 'Manforce Type Management'],
                    ['name' => 'Activity Category Management'],
                    ['name' => 'Activity Sub Category Management'],
                    ['name' => 'NCR/SOR Management'],
                ]
            ],
            [
                'name' => 'Planning and Scheduling',
                'sub_module' => [
                    ['name' => 'Non Working Day'],
                    ['name' => 'Manforce Management'],
                    ['name' => 'Material Management'],
                    ['name' => 'Machinery Management'],
                    ['name' => 'Stock Management'],
                    ['name' => 'Gantt Chart'],
                    ['name' => 'Activity Settings'],
                    ['name' => 'Scaffold Requirement'],
                    ['name' => 'Activity Progress'],
                    ['name' => 'Planned Manpower'],
                    ['name' => 'Material Sheet'],
                    ['name' => 'Actual Manpower'],
                    ['name' => 'Planned Machinerry'],
                    ['name' => 'Actual Work Completed'],
                    ['name' => 'Material Transfer Request'],
                    ['name' => 'Machinery Allocation'],
                    ['name' => 'Material Allocation'],
                    ['name' => 'Manforce Allocation'],
                    ['name' => 'Overtime'],
                    ['name' => 'Gangs Management'],
                    ['name' => 'Manforce Gang Management'],
                ]
            ],
            [
                'name' => 'Qa/Qc',
                'sub_module' => [
                    ['name' => 'Inspection'],
                    ['name' => 'Material Approval Log'],
                    ['name' => 'NCR/SOR Request'],
                    // ['name' => 'Method Statement Log'],
                    // ['name' => 'MSRA'],
                ]
            ],
            [
                'name' => 'Qs',
                'sub_module' => [
                    ['name' => 'Raising Material Requisition'],
                    ['name' => 'Raising Site Instruction'],
                    ['name' => 'Qs Manpower Cost'],
                ]
            ],
            [
                'name' => 'Design Team',
                'sub_module' => [
                    ['name' => 'Activity Document Management'],
                ]
            ],
            [
                'name' => 'HSE',
                'sub_module' => [
                    ['name' => 'Method Statement'],
                    ['name' => 'HSE Permits'],
                ]
            ],
        ];

        foreach ($modules as $mKey => $mVal) {
            $module = new Module();
            $module->name = $mVal['name'];
            $module->created_at = '';

            if ($module->save()) {
                if (isset($mVal['sub_module']) && !empty($mVal['sub_module'])) {
                    foreach ($mVal['sub_module'] as $subModKey => $subModVal) {
                        $subModule = new SubModule();
                        $subModule->module_id = $module->id;
                        $subModule->name = $subModVal['name'];
                        $subModule->created_at = '';
                        $subModule->save();
                    }
                }
            }
        }
    }
}
