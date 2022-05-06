<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\System\AdminUserSeeder;
use Database\Seeders\System\ModuleSubModuleSeeder;
use Database\Seeders\System\RoleSeeder;

class SystemDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            ModuleSubModuleSeeder::class,
            // RoleSeeder::class,
            // AdminUserSeeder::class
        ]);
    }
}
