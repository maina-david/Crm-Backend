<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCompanyIdToQueueLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('queue_logs', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained("companies", "id")->after("call_id");
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
            $table->dropColumn('company_id');
        });
    }
}
