<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIVRFlowsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ivr_flows', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("ivr_id");
            $table->string("flow_name");
            $table->string("application_type");
            $table->string("application_data");
            $table->unsignedBigInteger("parent_id")->nullable();
            $table->timestamps();

            $table->foreign('ivr_id')->references('id')->on('ivrs')->nullable();
            $table->foreign('parent_id')->references('id')->on('ivr_flows')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('i_v_r__flows');
    }
}
