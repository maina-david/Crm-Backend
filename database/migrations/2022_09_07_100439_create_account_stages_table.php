<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAccountStagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('account_stages', function (Blueprint $table) {
            $table->id();
            $table->string("first_name")->nullable();
            $table->string("middle_name")->nullable();
            $table->string("last_name")->nullable();
            $table->unsignedBigInteger("account_form_id");
            $table->unsignedBigInteger("account_type_id");
            $table->unsignedBigInteger("created_by");
            $table->unsignedBigInteger("approved_by")->nullable();
            $table->unsignedBigInteger("company_id");
            $table->string("approval_type");
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
        Schema::dropIfExists('account_stages');
    }
}
