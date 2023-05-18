<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChatBotFlowsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('chat_bot_flows', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("chatbot_id");
            $table->foreign('chatbot_id')->references('id')->on('chat_bots')->nullable();
            $table->string("flow_name");
            $table->string("application_type");
            $table->string('application_data')->nullable();
            $table->unsignedBigInteger("parent_id")->nullable();
            $table->foreign('parent_id')->references('id')->on('chat_bot_flows')->nullable()->onDelete('cascade');
            $table->unsignedBigInteger("ui_node_id")->nullable();
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
        Schema::dropIfExists('chat_bot_flows');
    }
}