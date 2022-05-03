<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddReceiverStatusToProjectsMaterialsTransferRequests extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('projects_materials_transfer_requests', function (Blueprint $table) {
            $table->tinyInteger('receiver_status')->after('reject_reasone')->default(1)->comment('1 - Pending, 2 - Approved, 3 - Rejected');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('projects_materials_transfer_requests', function (Blueprint $table) {
            $table->dropColumn('receiver_status');
        });
    }
}
