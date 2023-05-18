<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_groups', function (Blueprint $table) {
            $table->unsignedBigInteger("user_id");
            $table->unsignedBigInteger("group_id");
            $table->unsignedBigInteger("company_id");
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users')->nullable();
            $table->foreign('group_id')->references('id')->on('groups')->nullable();
            $table->foreign('company_id')->references('id')->on('companies')->nullable();
            $table->primary(['user_id', 'group_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_groups');
    }
}
