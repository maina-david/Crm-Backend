<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHelpDeskTeamsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('help_desk_teams', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_leader_id');
            $table->foreign('team_leader_id')->references('id')->on('users');
            $table->foreignId('company_id')->constrained('companies');
            $table->text('name');
            $table->longText('description');
            $table->boolean('active')->default(true);
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
        Schema::dropIfExists('help_desk_teams');
    }
}
