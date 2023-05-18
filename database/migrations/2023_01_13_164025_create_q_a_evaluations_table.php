<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQAEvaluationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('q_a_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId("qa_team_id")->constrained("q_a_teams", "id");
            $table->foreignId("qa_form_id")->constrained("q_a_forms", "id");
            $table->foreignId("agent_id")->constrained("users", "id");
            $table->foreignId("assessed_by")->constrained("users", "id");
            $table->foreignId("review_id")->constrained("q_a_interaction_reviews", "id");
            $table->integer("assessment_total");
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
        Schema::dropIfExists('q_a_evaluations');
    }
}