<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInvitationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invitations', function (Blueprint $table) {
            $table->id();
            $table->string("email");
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('group_id')->nullable();
            $table->unsignedBigInteger('role_profile_id')->nullable();
            $table->unsignedBigInteger('invited_by');
            $table->string("status");
            $table->dateTime("accepted_at")->nullable();
            $table->timestamps();

            $table->foreign('invited_by')->references('id')->on('users')->nullable();
            $table->foreign('company_id')->references('id')->on('companies')->nullable();
            $table->foreign('group_id')->references('id')->on('groups')->nullable();
            $table->foreign('role_profile_id')->references('id')->on('role_profiles')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('invitations');
    }
}
