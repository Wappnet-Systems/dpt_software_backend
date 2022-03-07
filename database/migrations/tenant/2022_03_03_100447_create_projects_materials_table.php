<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectsMaterialsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('projects_materials', function (Blueprint $table) {
            $table->charset = 'utf8';
            $table->collation = 'utf8_general_ci';

            $table->id();
            $table->foreignId('projects_id')->constrained('projects')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('unit_type_id')->constrained('unit_types')->cascadeOnUpdate()->cascadeOnDelete();
            $table->double('quantity', 8, 2);
            $table->double('cost', 10, 2);
            $table->tinyInteger('status')->default(1)->comment('1 - Active, 2 - In Active, 3 - Deleted');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
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
        Schema::dropIfExists('projects_materials');
    }
}
