<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class WebhookCall extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('webhook_calls', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('url')->nullable();
            $table->json('headers')->nullable();
            $table->json('payload')->nullable();
            $table->text('exception')->nullable();

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
        //
    }
}
