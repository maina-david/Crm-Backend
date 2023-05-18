<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMohFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('moh_files', function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->string("file_url");
            $table->unsignedBigInteger("sequence");
            $table->unsignedBigInteger("moh_id");
            $table->unsignedBigInteger("company_id");
            $table->timestamps();
            $table->foreign('moh_id')->references('id')->on('music_on_holds')->nullable();
            $table->foreign('company_id')->references('id')->on('companies')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('moh_files');
    }
}
