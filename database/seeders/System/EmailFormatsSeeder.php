<?php

namespace Database\Seeders\System;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\System\EmailFormat;

class EmailFormatsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('email_formats')->truncate();

        $emails = [
            [
                'title' => "Forgot Password",
                'variables' => "",
                'subject' => "Forgot Password",
                'emailformat' => 'Hello %full_name%,<br />
                <br />
                Welcome to Digital Project Tracker Here send your reset password link click on reset your password.<br />
                <br />
                Link :-&nbsp; <a href="%link%" target="_blank">Click here</a><br />
                <br />
                Reset Url :- %link%<br />
                <br />
                Thank you<br />
                Digital Project Tracker Team',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'title' => "Account Infomation",
                'variables' => "",
                'subject' => "Account Infomation",
                'emailformat' => 'Hello %full_name%,<br />
                <br />
                Welcome to Digital Project Tracker, Here we send your account details below.<br />
                <br />
                Organization Name : %organization_name%<br />
                Email : %email%<br />
                Password : %password%<br />
                <br />
                Thank you<br />
                Digital Project Tracker Team',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ];

        foreach ($emails as $email) {
            EmailFormat::insertGetId($email);
        }
    }
}
