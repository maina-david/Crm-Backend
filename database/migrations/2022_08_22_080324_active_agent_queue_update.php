<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ActiveAgentQueueUpdate extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('active_agent_queues', function (Blueprint $table) {
            $table->string("sip_status")->after("status")->default(0);
            $table->string("penality")->after("sip_status")->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('active_agent_queues', function (Blueprint $table) {
            $table->dropColumn('sip_status');
            $table->dropColumn('penality');
        });
    }
}
