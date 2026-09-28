<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('technician')->index();
            $table->string('api_token', 64)->nullable()->unique();
            $table->boolean('active')->default(true);
        });

        Schema::create('employees', function (Blueprint $table) {
            $table->id(); $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('phone')->nullable(); $table->string('specialty')->nullable();
            $table->date('hired_at')->nullable(); $table->boolean('active')->default(true); $table->timestamps(); $table->softDeletes();
        });
        Schema::create('customers', function (Blueprint $table) {
            $table->id(); $table->string('name')->index(); $table->string('document')->nullable()->unique();
            $table->string('phone'); $table->string('whatsapp')->nullable(); $table->string('email')->nullable();
            $table->text('notes')->nullable(); $table->boolean('active')->default(true); $table->timestamps(); $table->softDeletes();
        });
        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->id(); $table->foreignId('customer_id')->constrained()->cascadeOnDelete(); $table->string('label');
            $table->string('zip_code')->nullable(); $table->string('street'); $table->string('number'); $table->string('complement')->nullable();
            $table->string('district'); $table->string('city'); $table->char('state', 2); $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable(); $table->timestamps(); $table->softDeletes();
        });
        Schema::create('customer_equipment', function (Blueprint $table) {
            $table->id(); $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('address_id')->nullable()->constrained('customer_addresses')->nullOnDelete(); $table->string('location');
            $table->string('type'); $table->string('brand')->nullable(); $table->string('model')->nullable(); $table->unsignedInteger('btus')->nullable();
            $table->string('voltage')->nullable(); $table->string('serial_number')->nullable(); $table->string('refrigerant_gas')->nullable();
            $table->date('installed_at')->nullable(); $table->date('purchased_at')->nullable(); $table->date('warranty_until')->nullable();
            $table->unsignedSmallInteger('maintenance_interval_months')->nullable(); $table->date('last_maintenance_at')->nullable();
            $table->date('next_maintenance_at')->nullable()->index(); $table->text('notes')->nullable(); $table->boolean('active')->default(true);
            $table->timestamps(); $table->softDeletes();
        });
        Schema::create('services', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('category')->nullable(); $table->text('description')->nullable();
            $table->decimal('default_price', 12, 2)->default(0); $table->decimal('estimated_cost', 12, 2)->default(0);
            $table->unsignedSmallInteger('average_minutes')->nullable(); $table->boolean('active')->default(true); $table->timestamps(); $table->softDeletes();
        });
        Schema::create('products', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('category')->nullable(); $table->string('unit')->default('un');
            $table->string('brand')->nullable(); $table->string('code')->nullable()->unique(); $table->decimal('cost_price', 12, 2)->default(0);
            $table->decimal('sale_price', 12, 2)->default(0); $table->decimal('stock', 12, 3)->default(0);
            $table->decimal('minimum_stock', 12, 3)->default(0); $table->boolean('active')->default(true); $table->timestamps(); $table->softDeletes();
        });
        Schema::create('appointments', function (Blueprint $table) {
            $table->id(); $table->foreignId('customer_id')->constrained(); $table->foreignId('address_id')->constrained('customer_addresses');
            $table->foreignId('equipment_id')->nullable()->constrained('customer_equipment')->nullOnDelete();
            $table->foreignId('technician_id')->nullable()->constrained('employees')->nullOnDelete(); $table->dateTime('scheduled_at')->index();
            $table->unsignedSmallInteger('duration_minutes')->default(60); $table->string('type'); $table->text('notes')->nullable();
            $table->string('status')->default('scheduled')->index(); $table->timestamps(); $table->softDeletes();
        });
        Schema::create('quotes', function (Blueprint $table) {
            $table->id(); $table->string('number')->unique(); $table->foreignId('customer_id')->constrained();
            $table->foreignId('address_id')->nullable()->constrained('customer_addresses')->nullOnDelete(); $table->string('status')->default('awaiting_approval');
            $table->decimal('discount', 12, 2)->default(0); $table->decimal('total', 12, 2)->default(0); $table->date('valid_until')->nullable();
            $table->text('notes')->nullable(); $table->timestamps(); $table->softDeletes();
        });
        Schema::create('work_orders', function (Blueprint $table) {
            $table->id(); $table->string('number')->unique(); $table->foreignId('customer_id')->constrained();
            $table->foreignId('address_id')->constrained('customer_addresses'); $table->foreignId('equipment_id')->nullable()->constrained('customer_equipment')->nullOnDelete();
            $table->foreignId('technician_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('appointment_id')->nullable()->unique()->constrained()->nullOnDelete(); $table->foreignId('quote_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('scheduled_at')->index(); $table->dateTime('started_at')->nullable(); $table->dateTime('finished_at')->nullable();
            $table->text('problem_reported')->nullable(); $table->text('diagnosis')->nullable(); $table->text('solution')->nullable();
            $table->text('recommendations')->nullable(); $table->text('notes')->nullable(); $table->string('status')->default('scheduled')->index();
            $table->decimal('subtotal', 12, 2)->default(0); $table->decimal('discount', 12, 2)->default(0); $table->decimal('total', 12, 2)->default(0);
            $table->timestamps(); $table->softDeletes();
        });
        Schema::create('work_order_status_history', function (Blueprint $table) {
            $table->id(); $table->foreignId('work_order_id')->constrained()->cascadeOnDelete(); $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('from_status')->nullable(); $table->string('to_status'); $table->dateTime('occurred_at')->index(); $table->text('notes')->nullable(); $table->timestamps();
        });
        Schema::create('work_order_services', function (Blueprint $table) {
            $table->id(); $table->foreignId('work_order_id')->constrained()->cascadeOnDelete(); $table->foreignId('service_id')->constrained();
            $table->string('name'); $table->decimal('quantity', 10, 2)->default(1); $table->decimal('unit_price', 12, 2); $table->decimal('discount', 12, 2)->default(0); $table->decimal('total', 12, 2); $table->timestamps();
        });
        Schema::create('work_order_products', function (Blueprint $table) {
            $table->id(); $table->foreignId('work_order_id')->constrained()->cascadeOnDelete(); $table->foreignId('product_id')->constrained();
            $table->string('name'); $table->decimal('quantity', 10, 3)->default(1); $table->decimal('unit_price', 12, 2); $table->decimal('discount', 12, 2)->default(0); $table->decimal('total', 12, 2); $table->timestamps();
        });
        Schema::create('payments', function (Blueprint $table) {
            $table->id(); $table->foreignId('work_order_id')->constrained()->cascadeOnDelete(); $table->decimal('amount', 12, 2);
            $table->string('method'); $table->dateTime('paid_at'); $table->text('notes')->nullable(); $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamps();
        });
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id(); $table->foreignId('product_id')->constrained(); $table->foreignId('work_order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); $table->string('type'); $table->decimal('quantity', 12, 3);
            $table->decimal('balance_after', 12, 3); $table->text('reason')->nullable(); $table->dateTime('occurred_at'); $table->timestamps();
        });
        Schema::create('time_entries', function (Blueprint $table) {
            $table->id(); $table->foreignId('employee_id')->constrained()->cascadeOnDelete(); $table->date('work_date')->index();
            $table->string('type'); $table->dateTime('occurred_at'); $table->text('notes')->nullable(); $table->boolean('adjusted')->default(false); $table->timestamps();
        });
        Schema::create('attachments', function (Blueprint $table) {
            $table->id(); $table->foreignId('work_order_id')->constrained()->cascadeOnDelete(); $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('category'); $table->string('path'); $table->string('mime_type')->nullable(); $table->unsignedBigInteger('size')->nullable(); $table->timestamps();
        });
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id(); $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); $table->string('action');
            $table->string('auditable_type'); $table->unsignedBigInteger('auditable_id'); $table->json('old_values')->nullable(); $table->json('new_values')->nullable();
            $table->text('reason')->nullable(); $table->string('ip_address', 45)->nullable(); $table->timestamps(); $table->index(['auditable_type', 'auditable_id']);
        });
    }

    public function down(): void
    {
        foreach (['audit_logs','attachments','time_entries','inventory_movements','payments','work_order_products','work_order_services','work_order_status_history','work_orders','quotes','appointments','products','services','customer_equipment','customer_addresses','customers','employees'] as $table) Schema::dropIfExists($table);
        Schema::table('users', function (Blueprint $table) { $table->dropColumn(['role','api_token','active']); });
    }
};
