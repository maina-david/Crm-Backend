<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ContactSocialAcctAddAccount extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('contact_social_accts', function (Blueprint $table) {
            $table->foreignId('account_id')->nullable()->constrained("accounts", "id")->after("id");
            $table->unsignedBigInteger('contact_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('contact_social_accts', function (Blueprint $table) {
            $table->dropColumn('account_id');
        });
    }
}
