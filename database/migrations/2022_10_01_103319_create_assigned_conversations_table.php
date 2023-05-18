<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAssignedConversationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('assigned_conversations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('channel_id');
            $table->foreignId('conversation_id')->constrained('conversations', 'id');
            $table->unsignedBigInteger('conv_queue_id');
            $table->foreign('conv_queue_id')->references('id')->on('conversation_queues');
            $table->foreignId('agent_id')->constrained('users', 'id');
            $table->string('status', 100);
            $table->dateTime('first_response')->nullable();
            $table->dateTime('closed_at')->nullable();
            $table->boolean('user_notified')->default(false);
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
        Schema::dropIfExists('assigned_conversations');
    }
}