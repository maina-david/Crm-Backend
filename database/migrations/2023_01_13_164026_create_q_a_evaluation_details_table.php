<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQAEvaluationDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('q_a_evaluation_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId("qa_evaluation_id")->constrained("q_a_evaluations", "id");
            $table->foreignId("form_item_id")->constrained("q_a_form_attrs", "id");
            $table->integer("score");
            $table->integer("result");
            $table->string("comment")->nullable();
            $table->boolean("is_mandatory")->default(false);
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
        Schema::dropIfExists('q_a_evaluation_details');
    }
}