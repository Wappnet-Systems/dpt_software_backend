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
            $table->tinyInteger('type')->comment('1 - Admin, 2 - Company Admin, 3 - Construction Site Admin, 4 - Engineer,  5 - Forman, 6 - Contractor, 7 - Sub Contractor');
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
