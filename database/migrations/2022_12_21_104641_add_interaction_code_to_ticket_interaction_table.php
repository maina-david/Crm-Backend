<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddInteractionCodeToTicketInteractionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ticket_interactions', function (Blueprint $table) {
            $table->string('interaction_code', 100)->nullable()->after('company_id')->unique();
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
            $table->dropColumn('interaction_code');
        });
    }
}