<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCompanyIdToTicketInteractionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ticket_interactions', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained('companies', 'id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ticket_interactions', function (Blueprint $table) {
            $table->dropColumn('company_id');
        });
    }
}