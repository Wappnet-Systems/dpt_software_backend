<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRoleHasSubModulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('role_has_sub_modules', function (Blueprint $table) {
            $table->charset = 'utf8';
            $table->collation = 'utf8_general_ci';

            $table->id();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnUpdate()->cascadeOnDelete();
            // $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('sub_module_id');
            $table->boolean('is_list')->default(false);
            $table->boolean('is_create')->default(false);
            $table->boolean('is_edit')->default(false);
            $table->boolean('is_delete')->default(false);
            $table->boolean('is_view')->default(false);
            $table->boolean('is_comment')->default(false);
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
        Schema::dropIfExists('role_has_sub_modules');
    }
}
