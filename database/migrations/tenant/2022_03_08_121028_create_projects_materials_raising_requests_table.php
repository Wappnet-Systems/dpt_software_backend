<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectsMaterialsRaisingRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('projects_materials_raising_requests', function (Blueprint $table) {
            $table->charset = 'utf8';
            $table->collation = 'utf8_general_ci';

            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('material_type_id')->constrained('material_types')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('unit_type_id')->constrained('unit_types')->cascadeOnUpdate()->cascadeOnDelete();
            $table->bigInteger('quantity');
            $table->tinyInteger('status')->default(1)->comment('1 - Pending, 2 - Approved, 3 - Rejected');
            $table->longText('reject_reasone')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->ipAddress('created_ip')->nullable();
            $table->ipAddress('updated_ip')->nullable();
            $table->softDeletes()->nullable();
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
        Schema::dropIfExists('projects_materials_raising_requests');
    }
}
