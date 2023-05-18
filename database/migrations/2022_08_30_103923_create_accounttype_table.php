<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAccounttypeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
{
    Schema::create('accounttype', function (Blueprint $table) {
       $table->id();
       $table->string('name');
       $table->text('description');
       $table->bigInteger('account_form_id');
       $table->bigInteger('contact_form_id');
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
        Schema::dropIfExists('accounttype');
    }
}
