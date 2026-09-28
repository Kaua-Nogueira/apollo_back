<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->string('legal_name')->nullable()->after('company_name');
            $table->string('state_registration')->nullable()->after('document');
            $table->string('municipal_registration')->nullable()->after('state_registration');
            $table->string('contact_name')->nullable()->after('municipal_registration');
            $table->string('website')->nullable()->after('email');
            $table->string('zip_code', 15)->nullable()->after('website');
            $table->string('street')->nullable()->after('zip_code');
            $table->string('number', 30)->nullable()->after('street');
            $table->string('complement')->nullable()->after('number');
            $table->string('district')->nullable()->after('complement');
            $table->string('pix_key')->nullable()->after('state');
            $table->string('slogan')->nullable()->after('pix_key');
            $table->string('logo_image')->nullable()->after('slogan');
            $table->string('document_header_image')->nullable()->after('logo_image');
            $table->string('document_footer_image')->nullable()->after('document_header_image');
            $table->string('document_accent_color', 9)->default('#15576A')->after('document_footer_image');
            $table->text('document_footer_text')->nullable()->after('document_accent_color');
            $table->text('quote_terms')->nullable()->after('document_footer_text');
            $table->text('receipt_terms')->nullable()->after('quote_terms');
        });

        DB::table('company_settings')->where('company_name', 'FrioFlow')->update(['company_name' => 'Minha empresa']);
        DB::table('users')->where('email', 'admin@frioflow.com.br')->update(['email' => 'admin@apollo.com.br']);
        DB::table('users')->where('email', 'joao@frioflow.com.br')->update(['email' => 'joao@apollo.com.br']);
        DB::table('users')->where('email', 'pedro@frioflow.com.br')->update(['email' => 'pedro@apollo.com.br']);
    }

    public function down(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->dropColumn([
                'legal_name', 'state_registration', 'municipal_registration', 'contact_name', 'website',
                'zip_code', 'street', 'number', 'complement', 'district', 'pix_key', 'slogan', 'logo_image',
                'document_header_image', 'document_footer_image', 'document_accent_color', 'document_footer_text',
                'quote_terms', 'receipt_terms',
            ]);
        });
    }
};
