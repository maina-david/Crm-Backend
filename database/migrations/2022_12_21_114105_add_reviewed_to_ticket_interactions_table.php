<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddReviewedToTicketInteractionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ticket_interactions', function (Blueprint $table) {
            $table->boolean('reviewed')->nullable()->after('interaction_reference')->default(false);
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
            $table->dropColumn('reviewed');
        });
    }
}