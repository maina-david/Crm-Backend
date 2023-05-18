<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQATeamQueuesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('q_a_team_queues', function (Blueprint $table) {
            $table->id();
            $table->foreignId("team_id")->constrained("q_a_teams", "id");
            $table->unsignedBigInteger("queue_id");
            $table->string("queue_type");
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
        Schema::dropIfExists('q_a_team_queues');
    }
}
