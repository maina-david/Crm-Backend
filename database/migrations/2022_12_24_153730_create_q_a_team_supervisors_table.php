<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQATeamSupervisorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('q_a_team_supervisors', function (Blueprint $table) {
            $table->id();
            $table->foreignId("team_id")->constrained("q_a_teams", "id");
            $table->foreignId("user_id")->constrained("users", "id");
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
        Schema::dropIfExists('q_a_team_supervisors');
    }
}
