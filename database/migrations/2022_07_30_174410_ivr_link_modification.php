<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class IvrLinkModification extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ivr_links', function (Blueprint $table) {
            $table->unsignedInteger('ivr_id')->after("ivr_flow_id");
            $table->string('selection')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ivr_links', function (Blueprint $table) {
            $table->dropColumn('ivr_id');
        });
    }
}
