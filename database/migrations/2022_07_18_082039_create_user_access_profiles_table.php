<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserAccessProfilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_access_profiles', function (Blueprint $table) {
            $table->unsignedBigInteger("user_id")->unique();
            $table->unsignedBigInteger("access_profile_id");
            $table->unsignedBigInteger("company_id");
            $table->foreign('user_id')->references('id')->on('users')->nullable();
            $table->foreign('access_profile_id')->references('id')->on('access_profiles')->nullable();
            $table->foreign('company_id')->references('id')->on('companies')->nullable();
            $table->primary(['user_id']);
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
        Schema::dropIfExists('user_access_profiles');
    }
}
