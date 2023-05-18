<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQATeamMembersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('q_a_team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId("q_a_team_id")->constrained("q_a_teams", "id");
            $table->foreignId("member_id")->constrained("users", "id");
            $table->boolean("is_available")->default(true);
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
        Schema::dropIfExists('q_a_team_queue_members');
    }
}