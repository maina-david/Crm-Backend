<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TicketEscationsAddEscationForm extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ticket_escalations', function (Blueprint $table) {
            $table->foreignId('escalation_form_id')->nullable()->constrained("escalation_forms", "form_id")->after("ticket_entry_id");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ticket_escalations', function (Blueprint $table) {
            $table->dropColumn('escation_form_id');
        });
    }
}
