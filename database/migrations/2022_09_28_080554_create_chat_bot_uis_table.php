<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChatBotUisTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('chat_bot_uis', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("chatbot_id");
            $table->foreign('chatbot_id')->references('id')->on('chat_bots')->nullable();
            $table->longText("ui_data");
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
        Schema::dropIfExists('chat_bot_uis');
    }
}
