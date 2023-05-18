<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVoiceBroadcastSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('voice_broadcast_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId("campaign_id")->constrained("campaigns");
            $table->string("audio_url");
            $table->foreignId("did")->constrained("did_lists");
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
        Schema::dropIfExists('voice_broadcast_settings');
    }
}
