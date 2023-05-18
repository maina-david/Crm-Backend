<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContactAttrsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contact_attrs', function (Blueprint $table) {
            $table->id();
            $table->bigInteger("contact_id");
            $table->bigInteger("contact_form_item_id");
            $table->string("value");
            $table->string("opt_value");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('contact_attrs');
    }
}
