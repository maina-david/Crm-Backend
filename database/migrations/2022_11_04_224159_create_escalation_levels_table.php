<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEscalationLevelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('escalation_levels', function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->foreignId("helpdesk_id")->constrained("help_desk_teams", "id");
            $table->foreignId("form_id")->constrained("ticket_forms", "id");
            $table->unsignedBigInteger("sequence");
            $table->foreignId("escalation_point_id")->constrained("escalation_points", "id");
            $table->integer("sla");
            $table->string("sla_measurement");
            $table->foreignId("company_id")->constrained("companies", "id");
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
        Schema::dropIfExists('escalation_levels');
    }
}