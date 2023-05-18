<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTicketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("account_id")->nullable();
            $table->foreignId('priority_id')->constrained('ticket_priorities', 'id');
            $table->foreignId('company_id')->constrained('companies');
            $table->unsignedBigInteger("created_by");
            $table->foreign('created_by')->references('id')->on('users');
            $table->string('created_from', 100);
            $table->string('status', 100);
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
        Schema::dropIfExists('tickets');
    }
}