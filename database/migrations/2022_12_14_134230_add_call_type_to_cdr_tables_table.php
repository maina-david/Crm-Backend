<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCallTypeToCdrTablesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cdr_tables', function (Blueprint $table) {
            $table->string('call_type')->nullable()->default("INBOUND")->after("desposition");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cdr_tables', function (Blueprint $table) {
            $table->dropColumn('call_type');
        });
    }
}
