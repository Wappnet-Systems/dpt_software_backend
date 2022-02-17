<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\System\AdminUserSeeder;
use Database\Seeders\System\EmailFormatsSeeder;

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
            AdminUserSeeder::class,
            EmailFormatsSeeder::class
        ]);
    }
}
