<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectsActivitiesAllocateMachineriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('projects_activities_allocate_machineries', function (Blueprint $table) {
            $table->charset = 'utf8';
            $table->collation = 'utf8_general_ci';

            $table->id();
            $table->unsignedBigInteger('project_activity_id');
            $table->foreignId('machinery_id')->constrained('machineries')->cascadeOnUpdate()->cascadeOnDelete();
            $table->date('date');
            $table->string('time_slots', 300);
            $table->unsignedBigInteger('assign_by');
            $table->ipAddress('created_ip')->nullable();
            $table->ipAddress('updated_ip')->nullable();
            $table->timestamps();
            
            $table->foreign('project_activity_id', 'pro_activity_allocate_machinery_project_activity_id_foreign')->references('id')->on('projects_activities')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('projects_activities_allocate_machineries');
    }
}
