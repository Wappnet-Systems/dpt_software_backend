<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectsManforcesProductivitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('projects_manforces_productivities', function (Blueprint $table) {
            $table->charset = 'utf8';
            $table->collation = 'utf8_general_ci';

            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('activity_subcategory_id')->constrained('activity_sub_categories')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('project_manforces_id')->constrained('projects_manforces')->cascadeOnUpdate()->cascadeOnDelete();
            $table->double('rate')->nullable();
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
        Schema::dropIfExists('projects_manforces_productivities');
    }
}
