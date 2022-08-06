<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectsActivitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('projects_activities', function (Blueprint $table) {
            $table->charset = 'utf8';
            $table->collation = 'utf8_general_ci';

            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('project_main_activity_id')->constrained('projects_main_activities')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('activity_sub_category_id')->nullable()->constrained('activity_sub_categories')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('manforce_type_id')->nullable()->constrained('manforce_types')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('name', 50);
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->timestamp('actual_start_date')->nullable();
            $table->timestamp('actual_end_date')->nullable();
            $table->string('location', 50)->nullable();
            $table->string('level', 30)->nullable();
            $table->double('actual_area', 50)->nullable();
            $table->double('completed_area', 50)->nullable();
            $table->foreignId('unit_type_id')->nullable()->constrained('unit_types')->cascadeOnUpdate()->cascadeOnDelete();
            $table->double('cost')->nullable();
            $table->boolean('scaffold_requirement')->default(false);
            $table->boolean('helper')->default(false);
            $table->tinyInteger('status')->default(1)->comment('1 - Pending, 2 - Start, 3 - Hold, 4 - Completed');
            $table->double('productivity_rate')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('sort_by')->nullable();
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
        Schema::dropIfExists('projects_activities');
    }
}
