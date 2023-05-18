<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCampaignToQueueLogTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('queue_logs', function (Blueprint $table) {
            $table->foreignId("campaign_id")->nullable()->constrained("campaigns", "id");
            $table->foreignId("campaign_contact_id")->nullable()->constrained("campaign_contacts", "id");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('queue_logs', function (Blueprint $table) {
            $table->dropColumn('campaign_id');
            $table->dropColumn('campaign_contact_id');
        });
    }
}
