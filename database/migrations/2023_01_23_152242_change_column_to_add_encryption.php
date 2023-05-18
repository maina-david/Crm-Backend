<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeColumnToAddEncryption extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        /* This is a migration to change the column type of the table campaign_contacts. */
        Schema::table('campaign_contacts', function (Blueprint $table) {
            $table->longText("name")->change();
            $table->longText("phone_number")->change();
        });

        /* Changing the column type of the table accounts. */
        Schema::table('accounts', function (Blueprint $table) {
            $table->longText("first_name")->change();
            $table->longText("middle_name")->change();
            $table->longText("last_name")->change();
        });

        /* Changing the column type of the table account_stages. */
        Schema::table('account_stages', function (Blueprint $table) {
            $table->longText("first_name")->change();
            $table->longText("middle_name")->change();
            $table->longText("last_name")->change();
        });

        /* Changing the column type of the table account_data. */
        Schema::table('account_data', function (Blueprint $table) {
            $table->longText("value")->change();
        });

        /* Changing the column type of the table account_stage_data. */
        Schema::table('account_stage_data', function (Blueprint $table) {
            $table->longText("value")->change();
        });

        /* Changing the column type of the table contacts. */
        Schema::table('contacts', function (Blueprint $table) {
            $table->longText("first_name")->change();
            $table->longText("maiden_name")->change();
            $table->longText("last_name")->change();
        });

        /* Changing the column type of the table contact_data. */
        Schema::table('contact_data', function (Blueprint $table) {
            $table->longText("value")->change();
        });

        /* Changing the column type of the table contact_stages. */
        Schema::table('contact_stages', function (Blueprint $table) {
            $table->longText("first_name")->change();
            $table->longText("middle_name")->change();
            $table->longText("last_name")->change();
        });

        /* Changing the column type of the table contact_data_stages. */
        Schema::table('contact_stage_data', function (Blueprint $table) {
            $table->longText("value")->change();
        });

        /* This is a migration to change the column type of the table ticket_entries. */
        Schema::table("ticket_entries", function (Blueprint $table) {
            $table->longText("value")->change();
        });

        /* This is a migration to change the column type of the table tickets. */
        Schema::table("tickets", function (Blueprint $table) {
            $table->longText("contact")->change();
        });

        /* This is a migration to change the column type of the table email_settings. */
        Schema::table("email_settings", function (Blueprint $table) {
            $table->longText("password")->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        /* Changing the column type of the table campaign_contacts. */
        Schema::table('campaign_contacts', function (Blueprint $table) {
            $table->string("name")->change();
            $table->string("phone_number")->change();
        });

        /* Changing the column type of the table accounts. */
        Schema::table('accounts', function (Blueprint $table) {
            $table->string("first_name")->change();
            $table->string("middle_name")->change();
            $table->string("last_name")->change();
        });

        /* Changing the column type of the table account_stages. */
        Schema::table('account_stages', function (Blueprint $table) {
            $table->string("first_name")->change();
            $table->string("middle_name")->change();
            $table->string("last_name")->change();
        });

        /* Changing the column type of the table account_data. */
        Schema::table('account_data', function (Blueprint $table) {
            $table->text("value")->change();
        });

        /* Changing the column type of the table account_stage_data. */
        Schema::table('account_stage_data', function (Blueprint $table) {
            $table->text("value")->change();
        });

        /* Changing the column type of the table contacts. */
        Schema::table('contacts', function (Blueprint $table) {
            $table->string("first_name")->change();
            $table->string("maiden_name")->change();
            $table->string("last_name")->change();
        });

        /* Changing the column type of the table contact_data. */
        Schema::table('contact_data', function (Blueprint $table) {
            $table->text("value")->change();
        });

        /* Changing the column type of the table contact_stages. */
        Schema::table('contact_stages', function (Blueprint $table) {
            $table->string("first_name")->change();
            $table->string("middle_name")->change();
            $table->string("last_name")->change();
        });

        /* Changing the column type of the table contact_stage_data. */
        Schema::table('contact_stage_data', function (Blueprint $table) {
            $table->text("value")->change();
        });

        /* This is a migration to change the column type of the table ticket_entries. */
        Schema::table("ticket_entries", function (Blueprint $table) {
            $table->string("value")->change();
        });

        /* This is a migration to change the column type of the table tickets. */
        Schema::table("tickets", function (Blueprint $table) {
            $table->string("contact")->change();
        });

        /* This is a migration to change the column type of the table email_settings. */
        Schema::table("email_settings", function (Blueprint $table) {
            $table->string("password")->change();
        });
    }
}
