<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTicketFormRelationshipsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ticket_form_relationships', function (Blueprint $table) {
            $table->unsignedBigInteger("parent_form_id")->constrained("ticket_form_items");
            $table->unsignedBigInteger("child_form_id")->constrained("ticket_form_items");
            $table->unsignedBigInteger("ticket_form_option_id")->constrained("ticket_form_options");
            $table->timestamps();
            $table->primary(["parent_form_id", "child_form_id", "ticket_form_option_id"], "ticket_relation_id");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ticket_form_relationships');
    }
}
