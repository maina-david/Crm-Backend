<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCallcenterSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('callcenter_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("company_id");
            $table->integer("max_penality")->default(0);
            $table->integer("service_level")->default(20);
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
        Schema::dropIfExists('callcenter_settings');
    }
}
