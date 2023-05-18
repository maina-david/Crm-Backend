<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSlaStatusToTicketAssignmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ticket_assignments', function (Blueprint $table) {
            $table->string('sla_status', 100)->after('end_time')->nullable()->default('WITHIN-SLA');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ticket_assignments', function (Blueprint $table) {
            $table->dropColumn('sla_status');
        });
    }
}