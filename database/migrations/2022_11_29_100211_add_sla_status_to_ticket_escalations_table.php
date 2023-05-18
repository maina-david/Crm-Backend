<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSlaStatusToTicketEscalationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ticket_escalations', function (Blueprint $table) {
            $table->string('sla_status', 100)->nullable()->after('changed_by');
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
            $table->dropColumn('sla_status');
        });
    }
}