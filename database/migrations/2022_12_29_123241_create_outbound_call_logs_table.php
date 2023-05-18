<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOutboundCallLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('outbound_call_logs', function (Blueprint $table) {
            $table->id();
            $table->string("sip_channel");
            $table->string("phone_channel")->nullable();
            $table->string("sip_bridge")->nullable();
            $table->string("phone_bridge")->nullable();
            $table->string("sip_id");
            $table->string("status");
            $table->string("phone_number");
            $table->string("source");
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
        Schema::dropIfExists('outbound_call_logs');
    }
}
