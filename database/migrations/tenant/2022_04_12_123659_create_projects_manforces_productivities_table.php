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
            $table->unsignedBigInteger('activity_sub_category_id')->nullable();
            $table->foreignId('manforce_type_id')->nullable()->constrained('manforce_types')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('unit_type_id')->nullable()->constrained('unit_types')->cascadeOnUpdate()->cascadeOnDelete();
            $table->double('productivity_rate')->nullable();
            $table->timestamps();

            $table->foreign('activity_sub_category_id','pro_manforce_productivity_act_sub_cate_id_foreign')->references('id')->on('activity_sub_categories')->onUpdate('cascade')->onDelete('cascade');
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
