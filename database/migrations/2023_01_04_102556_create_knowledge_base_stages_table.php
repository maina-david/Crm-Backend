<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateKnowledgeBaseStagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('knowledge_base_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId("company_id")->constrained("companies", "id");
            $table->foreignId("knowledge_base_id")->nullable()->constrained("knowledge_bases", "id");
            $table->string("title");
            $table->longText("detail");
            $table->string("type");
            $table->string("status")->default("PENDING");
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
        Schema::dropIfExists('knowldge_base_stages');
    }
}
