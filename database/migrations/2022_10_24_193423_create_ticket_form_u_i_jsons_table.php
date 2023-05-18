<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTicketFormUIJsonsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ticket_form_u_i_jsons', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("ticket_form_id")->constrained("ticket_forms");
            $table->longText("json_ui");
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
        Schema::dropIfExists('ticket_form_u_i_jsons');
    }
}
