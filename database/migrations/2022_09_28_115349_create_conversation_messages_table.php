<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateConversationMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('conversation_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('conversations', 'id');
            $table->longText('message')->nullable();
            $table->string('message_type', 100)->default('text');
            $table->string('message_level', 100)->default('ON-BOT');
            $table->longText('attachment')->nullable();
            $table->longText('attachment_type')->nullable();
            $table->string('direction', 100);
            $table->foreignId('agent_id')->nullable()->constrained('users', 'id');
            $table->string('status', 100)->default('UNREAD');
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
        Schema::dropIfExists('conversation_messages');
    }
}