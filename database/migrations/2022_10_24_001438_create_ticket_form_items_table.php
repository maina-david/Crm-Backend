<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTicketFormItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ticket_form_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId("ticket_form_id")->constrained("ticket_forms");
            $table->string("lable")->nullable();
            $table->string("place_holder")->nullable();
            $table->string("ui_node_id");
            $table->unsignedInteger("sequence");
            $table->string("data_type");
            $table->foreignId("parent_id")->nullable()->constrained("ticket_form_items", "id");
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
        Schema::dropIfExists('ticket_form_items');
    }
}
