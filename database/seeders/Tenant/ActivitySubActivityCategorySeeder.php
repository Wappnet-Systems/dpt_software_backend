<?php

namespace Database\Seeders\Tenant;

use Illuminate\Database\Seeder;
use App\Models\Tenant\ActivityCategory;
use App\Models\Tenant\ActivitySubCategory;
use App\Models\Tenant\UnitType;

class ActivitySubActivityCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $activityCategoryArr = [
            'Formwork' => [
                'm2' => ['Stab Docking', 'Foundating shuttering', 'Column shuttering', 'Shear wall', 'Retaining wall', 'Upstand', 'Core wall', 'Grade slab', 'DownStand']
            ],
            'Staging' => [
                'm3' => ['Docking']
            ],
            'Steel reinforcement' => [
                'kg' =>  ['Slab', 'Foundation', 'Foundation-mat', 'Column', 'Shear wall', 'Retaining wall', 'Corewall', 'Grade slab', 'Upstand', 'Down Stand']
            ],
            'Concreting' => [
                'm3' => ['Foundation', 'Foundation-mat', 'Column', 'Shear wall', 'Retaining wall', 'Blinding', 'Screed', 'Corewall']
            ],
            'Surface Preparation' => [
                'm2' => ['Water Proofing', 'Concrete repair', 'Vertical element', 'Soil formation', 'Post-tension']
            ],
            'Post-tension' => [
                'lmorno' => ['Cable laying', 'Anchor-block and grout pipes']
            ],
            'Block work' => [
                'm2' => ['100 mm hollow', '150 mm hollowinsolated', '200 mm hollowinsolated', '100 mm solid', '1500 mm solid', '200 mm solid']
            ],
            'Painting' => [
                'm2' => ['Primer', 'Intermediate coat', 'Sanding', 'Final coat']
            ],
            'Tile fixing' => [
                'm2' => ['Floor filing', 'Wall tiling']
            ],
        ];

        foreach ($activityCategoryArr as $categoryKey => $unitTypeNameArr) {
            $activityCategory = new ActivityCategory();
            $activityCategory->name = $categoryKey;
            $activityCategory->save();

            foreach ($unitTypeNameArr as $unitTypeNameKey => $subCategoryValueArr) {
                foreach ($subCategoryValueArr as $subCategoryValue) {
                    $subCategory = new ActivitySubCategory();

                    $unitTypeCheck = UnitType::whereName($unitTypeNameKey)->first();

                    if (!isset($unitTypeCheck) && empty($unitTypeCheck)) {
                        $unitTypes = new UnitType();
                        $unitTypes->name = $unitTypeNameKey;
                        $unitTypes->save();
                    }

                    $subCategory->activity_category_id = $activityCategory->id;
                    $subCategory->name = $subCategoryValue;
                    $subCategory->unit_type_id = !empty($unitTypeCheck) ? $unitTypeCheck->id : $unitTypes->id;
                    $subCategory->save();
                }
            }
        }
    }
}
