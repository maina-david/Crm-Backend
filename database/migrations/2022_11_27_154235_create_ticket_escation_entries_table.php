<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTicketEscationEntriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ticket_escation_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId("ticket_escation_id")->constrained("ticket_escalations", "id");
            $table->foreignId("escation_form_item_id")->constrained("form_attributes", "id");
            $table->string("value");
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
        Schema::dropIfExists('ticket_escation_entries');
    }
}
