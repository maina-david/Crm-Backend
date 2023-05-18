<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCallIvrLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('call_ivr_logs', function (Blueprint $table) {
            $table->id();
            $table->string("call_log_id");
            $table->string("call_id");
            $table->string("currnt_ivr_flow");
            $table->string("data")->nullable();
            $table->string("status")->nullable();
            $table->integer("retry")->default(0);
            $table->string("next_ivr_flow")->nullable();
            $table->foreignId("company_id")->constrained();;
            $table->timestamps();
            $table->foreign('call_log_id')->references('call_id')->on('call_logs')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('call_ivr_logs');
    }
}
