<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('company_settings', function (Blueprint $table) {
            $table->id();
            $table->string('company_name')->default('FrioFlow');
            $table->string('document')->nullable();
            $table->string('phone')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('email')->nullable();
            $table->string('city')->nullable();
            $table->string('state', 2)->nullable();
            $table->string('timezone')->default('America/Sao_Paulo');
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('company_settings'); }
};
