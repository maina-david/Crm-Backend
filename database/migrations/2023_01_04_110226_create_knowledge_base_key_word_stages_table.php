<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateKnowledgeBaseKeyWordStagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('knowledge_base_key_word_stages', function (Blueprint $table) {
            $table->foreignId("knowledge_base_stage_id")->constrained("knowledge_base_stages");
            $table->foreignId("key_word_id")->constrained("key_words", "id");
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
        Schema::dropIfExists('knowldge_base_key_word_stages');
    }
}
