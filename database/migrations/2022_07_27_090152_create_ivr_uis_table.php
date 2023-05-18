<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIVRUisTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ivr_uis', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("ivr_id");
            $table->longText("ui_data");
            $table->timestamps();
            $table->foreign('ivr_id')->references('id')->on('ivrs')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('i_v_r_uis');
    }
}
