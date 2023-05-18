<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAgentLedCampaignSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('agent_led_campaign_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId("campaign_id")->constrained("campaigns");
            $table->foreignId("queue_id")->constrained("queues");
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
        Schema::dropIfExists('agent_led_campaign_settings');
    }
}
