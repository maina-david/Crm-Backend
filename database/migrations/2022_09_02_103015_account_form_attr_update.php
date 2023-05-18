<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AccountFormAttrUpdate extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('account_form_attrs', function (Blueprint $table) {
            $table->string("place_holder")->after("data_name")->default("");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('account_form_attrs', function (Blueprint $table) {
            $table->dropColumn('place_holder');
        });
    }
}
