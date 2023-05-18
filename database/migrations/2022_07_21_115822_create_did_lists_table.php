<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDidListsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('did_lists', function (Blueprint $table) {
            $table->id();
            $table->string("did");
            $table->string("allocation_status")->default("FREE");
            $table->unsignedBigInteger("company_id")->nullable();
            $table->unsignedBigInteger("carrier_id")->nullable();
            $table->timestamps();
            $table->foreign('company_id')->references('id')->on('companies')->nullable();
            $table->foreign('carrier_id')->references('id')->on('carriers')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('did_lists');
    }
}
