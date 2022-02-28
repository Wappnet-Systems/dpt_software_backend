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
                'name' => 'Planning and Scheduling',
                'sub_module' => [
                    ['name' => 'Gant Chart'],
                    ['name' => 'Activity Settings'],
                    ['name' => 'Planned Manpower'],
                    ['name' => 'Material Sheet'],
                    ['name' => 'Actual Manpower'],
                    ['name' => 'Planned Machinerry'],
                    ['name' => 'Actual Work Completed'],
                ]
            ],
            [
                'name' => 'Qa/Qc',
                'sub_module' => [
                    ['name' => 'Inspection'],
                    ['name' => 'Material Approval Log'],
                    ['name' => 'NCR/SOR'],
                    ['name' => 'Method Statement Log'],
                    ['name' => 'MSRA'],
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
                    ['name' => 'Upload Drawings'],
                ]
            ],
            [
                'name' => 'HSE',
                'sub_module' => [
                    ['name' => 'Risk Assessment Panel'],
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
