<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class IvrFlowModification extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ivr_flows', function (Blueprint $table) {
            $table->unsignedInteger('ui_node_id')->after("parent_id")->default(0);
            $table->string('application_data')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ivr_flows', function (Blueprint $table) {
            $table->dropColumn('ui_node_id');
        });
    }
}
