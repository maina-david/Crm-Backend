<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQueueLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('queue_logs', function (Blueprint $table) {
            $table->id();
            $table->string("call_id");
            $table->string("caller_id");
            $table->unsignedBigInteger("queue_id");
            $table->string("bridge_out_id")->nullable();
            $table->string("channel_in_id")->nullable();
            $table->string("bridge_in_id")->nullable();
            $table->string("status")->nullable();
            $table->string("sip_id")->nullable();
            $table->unsignedBigInteger("original_position")->nullable();
            $table->unsignedBigInteger("position")->nullable();
            $table->string("moh_play_id")->nullable();
            $table->longText("moh_files")->nullable();
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
        Schema::dropIfExists('queue_logs');
    }
}
