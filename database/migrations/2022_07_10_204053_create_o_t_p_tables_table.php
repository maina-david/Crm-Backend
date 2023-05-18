<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOTPTablesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('o_t_p_tables', function (Blueprint $table) {
            $table->id();
            $table->string("OTP_code");
            $table->dateTime("expires_at")->nullable();
            $table->string("OTP_type");
            $table->string("OTP_value");
            $table->string("status")->default("ACTIVE");
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
        Schema::dropIfExists('o_t_p_tables');
    }
}
