<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAccountFormAttrsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('account_form_attrs', function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->string("data_name");
            $table->boolean("is_required")->default(1);
            $table->string("data_type");
            $table->boolean('is_masked')->default(1);
            $table->unsignedBigInteger("account_form_id");
            $table->string("status")->default("ACTIVE");
            $table->integer("sequence");
            $table->unsignedBigInteger("company_id");
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
        Schema::dropIfExists('account_form_attrs');
    }
}
