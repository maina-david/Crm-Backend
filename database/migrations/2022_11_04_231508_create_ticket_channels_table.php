<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTicketChannelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ticket_channels', function (Blueprint $table) {
            $table->id();
            $table->foreignId("ticket_entry_id")->constrained("tickets", "id");
            $table->string("interaction_reference");
            $table->foreignId("channel_id")->constrained("channels", "id");
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
        Schema::dropIfExists('ticket_channels');
    }
}
