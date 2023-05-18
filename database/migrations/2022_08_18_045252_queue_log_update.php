<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class QueueLogUpdate extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('queue_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('group_id')->after("queue_id")->nullable();
            $table->unsignedBigInteger('user_id')->after("queue_id")->nullable();
            $table->date("call_date")->after("sip_id")->default(now());
            $table->decimal("queue_time",8,2)->after("call_date")->default(0);
            $table->decimal("call_time",8,2)->after("queue_time")->default(0);
            $table->decimal("hold_time",8,2)->after("call_time")->default(0);
            $table->decimal("mute_time",8,0)->after("hold_time")->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('queue_logs', function (Blueprint $table) {
            $table->dropColumn('group_id');
            $table->dropColumn('user_id');
            $table->dropColumn('call_date');
            $table->dropColumn('queue_time');
            $table->dropColumn('call_time');
            $table->dropColumn('hold_time');
            $table->dropColumn('mute_time');
        });
    }
}
