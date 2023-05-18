<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEscalationPointsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('escalation_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId("company_id")->constrained("companies", "id");
            $table->foreignId("priority_id")->nullable()->constrained("priorities", "id");
            $table->string("name");
            $table->string("description");
            $table->foreignId("ticket_form_id")->nullable()->constrained("ticket_forms", "id");
            $table->json("escalation_matrix")->nullable();
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
        Schema::dropIfExists('escalation_points');
    }
}