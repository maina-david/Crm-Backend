<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEscalationFormDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('escalation_form_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users', 'id');
            $table->foreignId('ticket_id')->constrained('tickets', 'id');
            $table->foreignId('form_id')->constrained('centralized_forms', 'id');
            $table->foreignId('escalation_level_id')->constrained('escalation_levels', 'id');
            $table->foreignId('escalation_point_id')->constrained('escalation_points', 'id');
            $table->foreignId('helpdesk_id')->constrained('help_desk_teams', 'id');
            $table->string('form_item_id', 100);
            $table->string('form_item_value', 100);
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
        Schema::dropIfExists('escalation_form_data');
    }
}