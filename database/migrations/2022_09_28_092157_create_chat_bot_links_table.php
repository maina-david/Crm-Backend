<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChatBotLinksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('chat_bot_links', function (Blueprint $table) {
            $table->id();
            $table->string('selection');
            $table->unsignedBigInteger('chatbot_id');
            $table->foreign('chatbot_id')->references('id')->on('chat_bots')->onDelete('cascade');
            $table->unsignedBigInteger("chatbot_flow_id");
            $table->foreign('chatbot_flow_id')->references('id')->on('chat_bot_flows')->nullable()->onDelete('cascade');
            $table->unsignedBigInteger("next_flow_id");
            $table->foreign('next_flow_id')->references('id')->on('chat_bot_flows')->nullable()->onDelete('cascade');
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
        Schema::dropIfExists('chat_bot_links');
    }
}