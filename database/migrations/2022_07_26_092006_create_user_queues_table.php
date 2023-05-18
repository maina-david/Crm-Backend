<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserQueuesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_queues', function (Blueprint $table) {
            $table->unsignedBigInteger("user_id");
            $table->unsignedBigInteger("queue_id");
            $table->unsignedBigInteger("company_id");
            $table->timestamps();
            $table->primary(['user_id', 'queue_id']);
            $table->foreign('user_id')->references('id')->on('users')->nullable();
            $table->foreign('queue_id')->references('id')->on('queues')->nullable();
            $table->foreign('company_id')->references('id')->on('companies')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_queues');
    }
}
