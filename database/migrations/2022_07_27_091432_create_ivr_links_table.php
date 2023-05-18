<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIVRLinksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ivr_links', function (Blueprint $table) {
            $table->id();
            $table->integer('selection');
            $table->unsignedBigInteger("next_flow_id");
            $table->unsignedBigInteger("ivr_flow_id");
            $table->timestamps();
            $table->foreign('ivr_flow_id')->references('id')->on('ivr_flows')->nullable();
            $table->foreign('next_flow_id')->references('id')->on('ivr_flows')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('i_v_r_links');
    }
}
