<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCallTransferLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('call_transfer_logs', function (Blueprint $table) {
            $table->id();
            $table->string("agent_channel");
            $table->string("phone_channel");
            $table->string("forwarded_channel")->nullable();
            $table->string("original_bridge");
            $table->string("transfer_bridge");
            $table->foreignId("transfered_by")->constrained("users", "id");
            $table->foreignId("transfered_to")->constrained("users", "id");
            $table->foreignId("queue_id")->nullable()->constrained("queues","id");
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
        Schema::dropIfExists('call_transfer_logs');
    }
}
