<?php

namespace Database\Seeders\Tenant;

use Illuminate\Database\Seeder;
use App\Models\Tenant\MaterialType;

class MaterialTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        MaterialType::truncate();

        $materialTypeArray = ['cement', 'steel', 'sand', 'concrete', 'screws'];

        foreach ($materialTypeArray as $value) {
            $unitType = new MaterialType();
            $unitType->name = $value;
            $unitType->save();
        }
    }
}
