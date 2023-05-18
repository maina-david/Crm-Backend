<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContactSocialAcctsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contact_social_accts', function (Blueprint $table) {
            $table->id();
            $table->foreignId("contact_id")->constrained("contacts", "id");
            $table->string("social_account");
            $table->foreignId("channel_id")->constrained("channels", "id");
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
        Schema::dropIfExists('contact_social_accts');
    }
}
