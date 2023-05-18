<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AgentStatusUpdate extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('agent_statuses', function (Blueprint $table) {
            $table->string("penality")->after("break_time")->default(0);
            $table->string("current_penality")->after("penality")->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('agent_statuses', function (Blueprint $table) {
            $table->dropColumn('penality');
            $table->dropColumn('current_penality');
        });
    }
}
