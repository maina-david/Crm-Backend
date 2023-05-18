<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CallLogsUpdate extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('call_logs', function (Blueprint $table) {
            $table->string("play_id")->after("call_status")->default(null);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('call_logs', function (Blueprint $table) {
            $table->dropColumn('play_id');
        });
    }
}
