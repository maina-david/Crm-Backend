<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAgentIdToQAInteractionReviewsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('q_a_interaction_reviews', function (Blueprint $table) {
            $table->foreignId('agent_id')->after('q_a_team_id')->nullable()->constrained('users', 'id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('q_a_interaction_reviews', function (Blueprint $table) {
            $table->dropColumn('agent_id');
        });
    }
}