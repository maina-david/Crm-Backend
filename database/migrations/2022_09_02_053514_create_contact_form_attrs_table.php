<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContactFormAttrsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contact_form_attrs', function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->string("data_name");
            $table->boolean("is_required")->default(false);
            $table->string("data_type");
            $table->boolean('is_masked')->default(false);
            $table->unsignedBigInteger("contact_form_id");
            $table->string("status")->default("ACTIVE");
            $table->integer("sequence");
            $table->unsignedBigInteger("company_id");
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
        Schema::dropIfExists('contact_form_attrs');
    }
}
