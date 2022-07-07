<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectsActivitiesTrackTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('projects_activities_track', function (Blueprint $table) {
            $table->charset = 'utf8';
            $table->collation = 'utf8_general_ci';

            $table->id();
            $table->foreignId('project_activity_id')->constrained('projects_activities')->cascadeOnUpdate()->cascadeOnDelete();
            $table->date('date')->nullable();
            $table->double('completed_area')->nullable();
            $table->double('productivity_rate')->nullable();
            $table->tinyInteger('status')->default(1)->comment('1 - Progress, 2 - Hold');
            $table->longText('comment')->nullable();
            $table->longText('reason')->nullable();
            $table->unsignedBigInteger('responsible_party')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
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
        Schema::dropIfExists('projects_activities_track');
    }
}
