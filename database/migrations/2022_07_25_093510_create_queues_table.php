<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQueuesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('queues', function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->string("description");
            $table->unsignedBigInteger("company_id");
            $table->unsignedBigInteger("group_id")->nullable();
            $table->unsignedBigInteger("moh_id")->nullable();
            $table->integer("wrap_up_time");
            $table->integer("time_out");
            $table->string("join_empty")->default("No");
            $table->string("leave_when_empty")->default("Yes");
            $table->string("status")->status("Active");
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->nullable();
            $table->foreign('group_id')->references('id')->on('groups')->nullable();
            $table->foreign('moh_id')->references('id')->on('music_on_holds')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('queues');
    }
}
