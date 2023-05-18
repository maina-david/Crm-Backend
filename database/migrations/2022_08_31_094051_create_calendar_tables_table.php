<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateCalendarTablesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('calendar_tables', function (Blueprint $table) {
            $table->date('d');
            $table->dateTime('dt');
            $table->boolean('is_weekend');
            $table->unsignedInteger('day');
            $table->unsignedInteger('month');
            $table->unsignedInteger('year');
            $table->unsignedInteger('week');
            $table->unsignedInteger('weekday');

            $table->string('month_name', 16);
            $table->string('weekday_name', 16);

            $table->primary('d');
            $table->index(['year', 'month', 'day'], 'date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('calendar_tables');
    }
}
