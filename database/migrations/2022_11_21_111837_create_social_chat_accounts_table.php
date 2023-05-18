<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSocialChatAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('social_chat_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId("account_id")->constrained("accounts","id");
            $table->string("socail_chat_id");
            $table->string("social_chat_username")->nullable();
            $table->foreignId("channel_id")->constrained("channels","id");
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
        Schema::dropIfExists('social_chat_accounts');
    }
}
