<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\System\AdminUserSeeder;

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
            AdminUserSeeder::class
        ]);
    }
}
