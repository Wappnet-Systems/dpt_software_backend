<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectsGangsManforcesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('projects_gangs_manforces', function (Blueprint $table) {
            $table->charset = 'utf8';
            $table->collation = 'utf8_general_ci';
            
            $table->id();
            $table->foreignId('gang_id')->constrained('projects_gangs')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('manforce_type_id')->constrained('manforce_types')->cascadeOnUpdate()->cascadeOnDelete();
            $table->tinyInteger('total_manforce');
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
        Schema::dropIfExists('projects_gangs_manforces');
    }
}
