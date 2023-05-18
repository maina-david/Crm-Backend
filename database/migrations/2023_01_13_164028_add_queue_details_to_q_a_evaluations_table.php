<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddQueueDetailsToQAEvaluationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('q_a_evaluations', function (Blueprint $table) {
            $table->string('queue_type', 100)->after('qa_team_id')->nullable();
            $table->string('queue_id', 100)->after('queue_type')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('q_a_evaluations', function (Blueprint $table) {
            $table->dropColumn('queue_type');
            $table->dropColumn('queue_id');
        });
    }
}