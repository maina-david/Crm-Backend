<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePendingTicketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pending_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId("ticket_entry_id")->constrained("tickets","id");
            $table->foreignId("agent_id")->constrained("users","id");
            $table->dateTime("notify_at");  
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
        Schema::dropIfExists('pending_tickets');
    }
}
