<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChatBotLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('chat_bot_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('conversations', 'id')->onDelete('cascade');
            $table->foreignId('chat_flow_id')->constrained('chat_bot_flows', 'id')->onDelete('cascade');
            $table->foreignId('current_flow_id')->constrained('chat_bot_flows', 'id')->onDelete('cascade');
            $table->bigInteger('selection')->nullable();
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
        Schema::dropIfExists('chat_bot_logs');
    }
}