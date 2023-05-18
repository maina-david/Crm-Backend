<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQAFormAttrsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('q_a_form_attrs', function (Blueprint $table) {
            $table->id();
            $table->foreignId("q_a_form_id")->constrained("q_a_forms", "id");
            $table->string("question");
            $table->string("type");
            $table->integer("weight");
            $table->integer("range")->nullable();
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
        Schema::dropIfExists('q_a_form_attrs');
    }
}