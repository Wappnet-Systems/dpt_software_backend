<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectsRaisingInstructionRequests extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('projects_raising_instruction_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnUpdate()->cascadeOnDelete();
            $table->bigInteger('request_no')->nullable();
            $table->longText('message');
            $table->tinyInteger('status')->default(1)->comment('1 - Pending, 2 - Approved, 3 - Rejected');
            $table->longText('reason')->nullable();
            $table->unsignedBigInteger('created_by');
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
        Schema::dropIfExists('projects_raising_instruction_requests');
    }
}
