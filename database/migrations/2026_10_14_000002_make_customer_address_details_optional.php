<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('customer_addresses', function (Blueprint $table) {
            $table->string('street', 150)->nullable()->change();
            $table->string('district', 100)->nullable()->change();
            $table->string('city', 100)->nullable()->change();
            $table->char('state', 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('customer_addresses')->whereNull('street')->update(['street' => '']);
        DB::table('customer_addresses')->whereNull('district')->update(['district' => '']);
        DB::table('customer_addresses')->whereNull('city')->update(['city' => '']);
        DB::table('customer_addresses')->whereNull('state')->update(['state' => '']);

        Schema::table('customer_addresses', function (Blueprint $table) {
            $table->string('street', 150)->nullable(false)->change();
            $table->string('district', 100)->nullable(false)->change();
            $table->string('city', 100)->nullable(false)->change();
            $table->char('state', 2)->nullable(false)->change();
        });
    }
};
