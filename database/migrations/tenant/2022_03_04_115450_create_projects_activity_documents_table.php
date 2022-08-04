<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectsActivityDocumentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('projects_activity_documents', function (Blueprint $table) {
            $table->charset = 'utf8';
            $table->collation = 'utf8_general_ci';

            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('project_activity_id')->nullable()->constrained('projects_activities')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('name');
            $table->string('path');
            $table->string('location', 50);
            $table->double('area');
            $table->tinyInteger('file_type')->comment('1 - Image, 2 - PDF');
            $table->tinyInteger('type')->comment('1 - Design/Drawings, 2 - Engineering Instruction,3 - Request For Information');
            $table->tinyInteger('status')->default(1)->comment('1 - Active, 2 - In Active, 3 - Deleted');
            $table->tinyInteger('discipline')->comment('1 - Architectural, 2 - Structural, 3 - Electrical, 4 - Mechanical, 5 - Plumbing, 6 - Interiors, 7 - Others');
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
        Schema::dropIfExists('projects_activity_documents');
    }
}
