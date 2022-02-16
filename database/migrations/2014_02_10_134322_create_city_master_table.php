<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCityMasterTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('city_master', function (Blueprint $table) {
            $table->charset = 'utf8';
            $table->collation = 'utf8_general_ci';
            
            $table->id();
            $table->foreignId('state_id')->nullable()->constrained('state_master')->nullOnDelete();
            $table->string('name', 50);
            $table->tinyInteger('status')->default(1)->comment('1 - Active, 2 - In Active');
            $table->softDeletes();
            $table->timestamps();

            $table->index('state_id', 'city_master_state_id_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('city_master');
    }
}
