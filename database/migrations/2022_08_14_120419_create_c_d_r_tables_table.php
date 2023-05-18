<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCDRTablesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cdr_tables', function (Blueprint $table) {
            $table->id();
            $table->string("call_id");
            $table->string("phone_number");
            $table->string("bridge_id");
            $table->string("group_id");
            $table->date("call_date");
            $table->decimal("call_time", 2, 2);
            $table->decimal("hold_time", 2, 2);
            $table->decimal("mute_time", 2, 2);
            $table->string("desposition");
            $table->string("sip_id");
            $table->unsignedBigInteger("user_id");
            $table->unsignedBigInteger("queue_id");
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
        Schema::dropIfExists('cdr_tables');
    }
}
