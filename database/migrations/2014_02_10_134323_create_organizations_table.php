<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrganizationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->charset = 'utf8';
            $table->collation = 'utf8_general_ci';

            $table->id();
            $table->foreignId('hostname_id')->constrained('hostnames')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('email', 50)->unique();
            $table->string('logo', 255)->nullable();
            $table->string('phone_no', 15)->nullable();
            $table->string('fax_no', 50)->nullable();
            $table->string('address', 100)->nullable();
            $table->foreignId('state_id')->nullable()->constrained('state_master')->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained('city_master')->nullOnDelete();
            $table->string('zip_code', 10)->nullable();
            $table->tinyInteger('status')->default(1)->comment('1 - Active, 2 - In Active, 3 - Deleted');
            $table->unsignedBigInteger('subscription_id')->nullable();
            $table->ipAddress('created_ip')->nullable();
            $table->ipAddress('updated_ip')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('id', 'organizations_id_index');
            $table->index('state_id', 'organizations_state_id_index');
            $table->index('city_id', 'organizations_city_id_index');
            $table->index('hostname_id', 'organizations_hostname_id_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('organizations');
    }
}
