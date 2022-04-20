<?php

namespace Database\Seeders\Tenant;

use Illuminate\Database\Seeder;
use App\Models\Tenant\UnitType;

class UnitTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // UnitType::truncate();

        $unitTypeArray = ['m2', 'm3', 'kg', 'lmorno', 'lm'];
        
        foreach ($unitTypeArray as $value) {
            $unitType = new UnitType();
            $unitType->name = $value;
            $unitType->save();
        }
    }
}
