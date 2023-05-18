<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCampaignContactsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('campaign_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId("campaign_id")->constrained("campaigns");
            $table->foreignId("contact_id")->nullable()->constrained("contacts");
            $table->string("name")->nullable();
            $table->string("phone_number")->nullable();
            $table->string("status");
            $table->string("desposition")->nullable();
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
        Schema::dropIfExists('campaign_contacts');
    }
}
