<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateActiveAgentQueuesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('active_agent_queues', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('queue_id');
            $table->unsignedBigInteger("sip_id");
            $table->unsignedBigInteger("user_id");
            $table->string("status")->default("ONLINE");
            $table->boolean("is_paused")->default(0);
            $table->dateTime("last_call_hung_up_at")->nullable();
            $table->unsignedBigInteger("company_id");
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
        Schema::dropIfExists('active_agent_queues');
    }
}
