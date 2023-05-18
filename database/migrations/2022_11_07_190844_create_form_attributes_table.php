<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFormAttributesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('form_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies', 'id');
            $table->string("name");
            $table->string("data_name");
            $table->boolean("is_required")->default(1);
            $table->string("data_type");
            $table->boolean('is_masked')->default(1);
            $table->foreignId('form_id')->constrained('centralized_forms', 'id');
            $table->string("status")->default("ACTIVE");
            $table->integer("sequence");
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
        Schema::dropIfExists('form_attributes');
    }
}