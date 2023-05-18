<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAccessProfilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('access_profiles', function (Blueprint $table) {
            $table->id();
            $table->string("access_name");
            $table->unsignedBigInteger("role_profile_id");
            $table->unsignedBigInteger("company_id");
            $table->foreign('role_profile_id')->references('id')->on('role_profiles')->nullable();
            $table->foreign('company_id')->references('id')->on('companies')->nullable();
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
        Schema::dropIfExists('access_profiles');
    }
}
