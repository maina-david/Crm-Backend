<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmailSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('email_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->string('outgoing_transport', 100)->default('smtp');
            $table->string('smtp_host', 100);
            $table->string('smtp_port', 100)->default('587');
            $table->string('incoming_transport', 100)->default('imap');
            $table->string('imap_host', 100);
            $table->string('imap_port', 100)->default('993');
            $table->string('encryption', 100)->default('tls');
            $table->string('username', 100);
            $table->longText('password');
            $table->string('timeout', 100)->nullable();
            $table->string('auth_mode', 100)->nullable();
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
        Schema::dropIfExists('email_settings');
    }
}