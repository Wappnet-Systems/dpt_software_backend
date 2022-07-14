<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectsActivitiesAllocateManforcesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('projects_activities_allocate_manforces', function (Blueprint $table) {
            $table->charset = 'utf8';
            $table->collation = 'utf8_general_ci';

            $table->id();
            $table->unsignedBigInteger('project_activity_id');
            $table->unsignedBigInteger('project_manforce_id');
            $table->date('date');
            $table->integer('total_assigned')->nullable();
            $table->integer('total_planned')->nullable();
            $table->boolean('is_overtime')->default(false);
            $table->integer('overtime_hours')->nullable();
            $table->double('total_work')->nullable();
            $table->double('total_cost')->nullable();
            $table->double('productivity_rate')->nullable();
            $table->unsignedBigInteger('assign_by');
            $table->ipAddress('created_ip')->nullable();
            $table->ipAddress('updated_ip')->nullable();
            $table->timestamps();

            $table->foreign('project_activity_id','pro_activity_allocate_manforce_project_activity_id_foreign')->references('id')->on('projects_activities')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('project_manforce_id','pro_activity_allocate_manforce_project_manforce_id_foreign')->references('id')->on('projects_manforces')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('projects_activities_allocate_manforces');
    }
}
