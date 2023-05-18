<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUiFormInEscalationPointsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('escalation_points', function (Blueprint $table) {
            $table->json('ui_form')->nullable()->after('escalation_matrix');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('escalation_points', function (Blueprint $table) {
            $table->dropColumn('ui_form');
        });
    }
}
