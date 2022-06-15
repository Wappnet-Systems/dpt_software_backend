<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectsScaffoldsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('projects_scaffolds', function (Blueprint $table) {
            $table->charset = 'utf8';
            $table->collation = 'utf8_general_ci';

            $table->id();
            $table->foreignId('project_activity_id')->constrained('projects_activities')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('scaffold_number', 50);
            $table->date('on_hire_date')->nullable();
            $table->date('off_hire_date')->nullable();
            $table->decimal('width', 10, 2)->nullable();
            $table->decimal('length', 10, 2)->nullable();
            $table->decimal('height', 10, 2)->nullable();
            $table->decimal('area', 10, 2)->nullable();
            $table->decimal('volume', 10, 2)->nullable();
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
        Schema::dropIfExists('projects_scaffolds');
    }
}
