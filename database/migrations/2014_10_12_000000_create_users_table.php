<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->charset = 'utf8';
            $table->collation = 'utf8_general_ci';
            
            $table->id();
            $table->uuid('user_uuid')->nullable();
            $table->string('name', 100);
            $table->string('email', 50)->unique();
            $table->string('personal_email', 50)->nullable()->unique();
            $table->string('password', 255)->nullable();
            $table->string('phone_number', 15)->nullable();
            $table->string('profile_image', 255)->nullable();
            $table->string('address', 255)->nullable();
            $table->double('lat', 10, 8)->nullable();
            $table->double('long', 11, 8)->nullable();
            $table->string('city', 25)->nullable();
            $table->string('state', 25)->nullable();
            $table->string('country', 25)->nullable();
            $table->string('zip_code', 10)->nullable();
            $table->tinyInteger('type')->comment('1 - Super Admin, 2 - Company Admin, 3 - Construction Site Admin, 4 - Manager, 5 - Project Engineer, 6 - QS Department, 7 - HSE Department, 8 - Design Department, 9 - Planner Engineer, 10 - Engineer, 11 - Foreman, 12 - QA/QC, 13 - Storkeeper, 14 - Timekeeper');
            $table->tinyInteger('status')->default(1)->comment('1 - Active, 2 - In Active, 3 - Deleted');
            $table->foreignId('organization_id')->nullable()->constrained('organizations')->cascadeOnUpdate()->cascadeOnDelete();
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->ipAddress('created_ip')->nullable();
            $table->ipAddress('updated_ip')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
