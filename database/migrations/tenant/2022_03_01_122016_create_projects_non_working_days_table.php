<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectsNonWorkingDaysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('projects_non_working_days', function (Blueprint $table) {
            $table->charset = 'utf8';
            $table->collation = 'utf8_general_ci';

            $table->id();
            // $table->foreignId('projects_id')->constrained('projects')->cascadeOnUpdate()->cascadeOnDelete();
            $table->unsignedBigInteger('projects_id')->nullable();
            $table->string('name', 50);
            $table->timestamp('start_date_time');
            $table->timestamp('end_date_time')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->tinyInteger('status')->default(1)->comment('1 - Active, 2 - In Active, 3 - Deleted');
            $table->ipAddress('created_ip')->nullable();
            $table->ipAddress('updated_ip')->nullable();
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
        Schema::dropIfExists('projects_non_working_days');
    }
}
