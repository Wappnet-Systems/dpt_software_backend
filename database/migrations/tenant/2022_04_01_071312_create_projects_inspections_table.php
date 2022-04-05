<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectsInspectionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('projects_inspections', function (Blueprint $table) {
            $table->charset = 'utf8';
            $table->collation = 'utf8_general_ci';

            $table->id();
            $table->foreignId('project_activity_id')->nullable()->constrained('projects_activities')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('project_allocate_material_id')->nullable()->constrained('projects_activities_allocate_materials')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('inspection_no', 30)->nullable();
            $table->date('inspection_date');
            $table->date('approve_reject_date')->nullable();
            $table->string('location', 50)->nullable();
            $table->string('document', 200)->nullable();
            $table->tinyInteger('inspection_type')->comment('1 - Internal, 2 - External');
            $table->tinyInteger('type')->comment('1 - Activity, 2 - Material');
            $table->tinyInteger('inspection_status')->default(1)->comment('1 - Pending, 2 - Approved, 3 - Rejected');
            $table->longText('comments')->nullable();
            $table->longText('reason')->nullable();
            $table->tinyInteger('status')->default(1)->comment('1 - Active, 2 - In Active');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');
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
        Schema::dropIfExists('projects_inspections');
    }
}
