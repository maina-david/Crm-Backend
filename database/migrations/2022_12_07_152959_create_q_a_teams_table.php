<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQATeamsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('q_a_teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId("company_id")->constrained("companies", "id");
            $table->string("name");
            $table->string("description");
            $table->foreignId("q_a_form_id")->nullable()->constrained("q_a_forms", "id");
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
        Schema::dropIfExists('q_a_teams');
    }
}