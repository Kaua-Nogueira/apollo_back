<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('quote_items', function (Blueprint $table) {
            $table->id(); $table->foreignId('quote_id')->constrained()->cascadeOnDelete();
            $table->string('type'); $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete(); $table->string('name');
            $table->decimal('quantity', 10, 3)->default(1); $table->decimal('unit_price', 12, 2); $table->decimal('discount', 12, 2)->default(0); $table->decimal('total', 12, 2); $table->timestamps();
        });
        Schema::create('receivables', function (Blueprint $table) {
            $table->id(); $table->foreignId('work_order_id')->unique()->constrained()->cascadeOnDelete(); $table->foreignId('customer_id')->constrained();
            $table->date('due_date')->nullable()->index(); $table->decimal('amount', 12, 2)->default(0); $table->decimal('received', 12, 2)->default(0); $table->string('status')->default('open')->index(); $table->timestamps();
        });
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id(); $table->string('license_plate')->unique(); $table->string('model'); $table->string('brand')->nullable(); $table->unsignedSmallInteger('year')->nullable(); $table->unsignedInteger('mileage')->default(0); $table->boolean('active')->default(true); $table->timestamps(); $table->softDeletes();
        });
        Schema::create('routes', function (Blueprint $table) {
            $table->id(); $table->foreignId('technician_id')->nullable()->constrained('employees')->nullOnDelete(); $table->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete(); $table->date('route_date')->index(); $table->string('status')->default('planned'); $table->text('notes')->nullable(); $table->timestamps(); $table->softDeletes();
        });
        Schema::create('route_stops', function (Blueprint $table) {
            $table->id(); $table->foreignId('route_id')->constrained()->cascadeOnDelete(); $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete(); $table->foreignId('work_order_id')->nullable()->constrained()->nullOnDelete(); $table->unsignedSmallInteger('position'); $table->dateTime('planned_at')->nullable(); $table->dateTime('arrived_at')->nullable(); $table->dateTime('completed_at')->nullable(); $table->timestamps();
        });
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id(); $table->string('key')->unique(); $table->string('name'); $table->string('channel')->default('whatsapp'); $table->text('body'); $table->boolean('active')->default(true); $table->timestamps();
        });
        Schema::create('notifications', function (Blueprint $table) {
            $table->id(); $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete(); $table->foreignId('work_order_id')->nullable()->constrained()->nullOnDelete(); $table->string('channel')->default('whatsapp'); $table->string('type'); $table->text('payload'); $table->string('status')->default('pending'); $table->dateTime('sent_at')->nullable(); $table->timestamps();
        });
    }
    public function down(): void { foreach(['notifications','notification_templates','route_stops','routes','vehicles','receivables','quote_items'] as $table) Schema::dropIfExists($table); }
};
