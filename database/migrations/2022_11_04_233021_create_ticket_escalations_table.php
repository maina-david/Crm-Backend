<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTicketEscalationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ticket_escalations', function (Blueprint $table) {
            $table->id();
            $table->foreignId("ticket_entry_id")->constrained("tickets", "id");
            $table->foreignId("escalation_point_id")->constrained("escalation_points", "id");
            $table->foreignId("escalation_level_id")->constrained("escalation_levels", "id");
            $table->foreignId("changed_by")->nullable()->constrained("users", "id");
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
        Schema::dropIfExists('ticket_escalations');
    }
}