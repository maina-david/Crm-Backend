<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOutboundSipDidsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('outbound_sip_dids', function (Blueprint $table) {
            $table->id();
            $table->foreignId("campany_id")->constrained("companies", "id");
            $table->foreignId("sip_id")->constrained("sip_lists", "id");
            $table->foreignId("did_id")->constrained("did_lists", "id");
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
        Schema::dropIfExists('outbound_sip_dids');
    }
}
