<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->charset = 'utf8';
            $table->collation = 'utf8_general_ci';
            
            $table->id();
            $table->uuid('uuid')->nullable();
            $table->string('name', 100);
            $table->string('logo', 255)->nullable();
            $table->string('address', 255)->nullable();
            $table->double('lat', 10, 8)->nullable();
            $table->double('long', 11, 8)->nullable();
            $table->string('city', 25)->nullable();
            $table->string('state', 25)->nullable();
            $table->string('country', 25)->nullable();
            $table->string('zip_code', 10)->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->double('cost')->nullable();
            $table->tinyInteger('status')->default(1)->comment('1 - Yet to Start, 2 - In Progress, 3 - Completed');
            $table->unsignedBigInteger('created_by')->nullable();
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
        Schema::dropIfExists('projects');
    }
}
