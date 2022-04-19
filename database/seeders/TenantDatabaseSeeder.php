<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\Tenant\UnitTypesSeeder;
use Database\Seeders\Tenant\MaterialTypesSeeder;
use Database\Seeders\Tenant\ActivitySubActivityCategorySeeder;
use Database\Seeders\Tenant\TimeSlotsSeeder;

class TenantDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            // UnitTypesSeeder::class,
            // MaterialTypesSeeder::class,
            // ActivitySubActivityCategorySeeder::class,
            TimeSlotsSeeder::class,
        ]);
    }
}
