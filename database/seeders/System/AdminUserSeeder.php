<?php

namespace Database\Seeders\System;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use App\Models\System\User;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user = User::create([
            'name' => "DPT Super Admin",
            'email' => 'dpt@mailinator.com',
            'password' => Hash::make('Try@123'),
            'type' => User::TYPE['Super Admin'],
            'email_verified_at' => Carbon::now()
        ]);
    }
}
