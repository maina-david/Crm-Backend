<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTicketCreationFormComponentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ticket_creation_form_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained('ticket_creation_forms', 'id');
            $table->string('name');
            $table->string('dataType')->nullable();
            $table->string('selectedOption')->nullable();
            $table->string('multipleOptions')->nullable();
            $table->string('checkBoxOptions')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('ticket_creation_form_components', 'id');  
            $table->bigInteger('nodeId')->nullable();
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
        Schema::dropIfExists('ticket_creation_form_components');
    }
}