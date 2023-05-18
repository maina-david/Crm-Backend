<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHelpDeskTeamTicketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('help_desk_team_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('help_desk_teams', 'id');
            $table->foreignId('ticket_id')->constrained('tickets', 'id');
            $table->string('status', 100);
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
        Schema::dropIfExists('help_desk_team_tickets');
    }
}