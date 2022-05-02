<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNcrSorRequest extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ncr_sor_request', function (Blueprint $table) {
            $table->charset = 'utf8';
            $table->collation = 'utf8_general_ci';
            
            $table->id();
            $table->tinyInteger('type')->default(1)->comment('1 - NCR, 2 - SOR');
            $table->string('path', 200)->nullable();
            $table->tinyInteger('status')->default(1)->comment('1 - Pending, 2 - Approve, 3 - Reject');
            $table->foreignId('project_activity_id')->nullable()->constrained('projects_activities')->cascadeOnUpdate()->cascadeOnDelete();
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
        Schema::dropIfExists('ncr_sor_request');
    }
}
