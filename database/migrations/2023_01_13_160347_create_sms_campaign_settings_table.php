<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSmsCampaignSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('sms_campaign_settings');
        Schema::create('sms_campaign_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId("campaign_id")->constrained("campaigns");
            $table->foreignId("sms_account_id")->constrained("sms_accounts");
            $table->longText("sms_text");
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
        Schema::dropIfExists('sms_campaign_settings');
    }
}
