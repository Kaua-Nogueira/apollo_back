<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('description', 180);
            $table->decimal('amount', 12, 2);
            $table->string('method', 30);
            $table->dateTime('paid_at');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index('paid_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
