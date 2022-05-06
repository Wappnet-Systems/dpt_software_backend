<?php

namespace Database\Seeders\Tenant;

use Illuminate\Database\Seeder;
use App\Models\Tenant\NcrSor;

class NcrSorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // UnitType::truncate();

        $types = [1,2];
        
        foreach ($types as $type) {
            $unitType = new NcrSor();
            $unitType->type = $type;
            $unitType->save();
        }
    }
}
