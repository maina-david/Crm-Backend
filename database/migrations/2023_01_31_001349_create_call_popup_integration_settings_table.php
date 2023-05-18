<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCallPopupIntegrationSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('call_popup_integration_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId("company_id")->constrained("companies", "id");
            $table->string("name");
            $table->string("type");
            $table->longText("url");
            $table->foreignId("scope")->nullable()->constrained("queues", "id");
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
        Schema::dropIfExists('call_popup_integration_settings');
    }
}