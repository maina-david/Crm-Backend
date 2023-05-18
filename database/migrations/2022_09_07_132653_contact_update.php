<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ContactUpdate extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->renameColumn("name", "first_name");
        });
        Schema::table('contacts', function (Blueprint $table) {
            $table->string("maiden_name")->after("first_name")->nullable();
            $table->string("last_name")->after("maiden_name")->nullable();
            $table->unsignedBigInteger("created_by")->after("company_id");
            $table->unsignedBigInteger("updated_by")->after("created_by")->nullable();
            $table->dropColumn('phone');
            $table->dropColumn('email');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('contacts', function(Blueprint $table) {
            $table->renameColumn('first_name', 'name');
            $table->dropColumn('maiden_name');
            $table->dropColumn('last_name');
            $table->dropColumn('created_by');
            $table->dropColumn('updated_by');
       
        });
    }
}
