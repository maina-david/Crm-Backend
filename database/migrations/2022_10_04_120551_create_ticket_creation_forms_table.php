<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTicketCreationFormsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ticket_creation_forms', function (Blueprint $table) {
            $table->id();   
            $table->foreignId('company_id')->constrained('companies', 'id');
            $table->unsignedBigInteger('account_id')->nullable();
            $table->string('name');
            $table->longText('description');
            $table->foreignId('priority_id')->constrained('ticket_priorities', 'id');
            $table->boolean('active')->default(true);
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
        Schema::dropIfExists('ticket_creation_forms');
    }
}