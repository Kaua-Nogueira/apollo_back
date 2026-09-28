<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApiFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_login_read_dashboard_and_create_customer(): void
    {
        $this->seed(DatabaseSeeder::class);
        $login = $this->postJson('/api/auth/login', ['email'=>'admin@apollo.com.br','password'=>'password'])
            ->assertOk()->assertJsonPath('data.user.role', 'admin');
        $token = $login->json('data.token');

        $this->withToken($token)->getJson('/api/dashboard')->assertOk()->assertJsonStructure(['data'=>['metrics','upcoming']]);
        $this->withToken($token)->postJson('/api/customers', ['name'=>'Novo Cliente','phone'=>'(98) 99999-9999'])
            ->assertCreated()->assertJsonPath('data.name', 'Novo Cliente');
    }

    public function test_work_order_transition_is_persisted_in_history(): void
    {
        $this->seed(DatabaseSeeder::class);
        $token = $this->postJson('/api/auth/login', ['email'=>'admin@apollo.com.br','password'=>'password'])->json('data.token');
        $this->withToken($token)->postJson('/api/work-orders/2/transition', ['action'=>'complete'])
            ->assertOk()->assertJsonPath('data.status', 'completed');
        $this->assertDatabaseHas('work_order_status_history', ['work_order_id'=>2,'to_status'=>'completed']);
        $this->assertDatabaseHas('audit_logs', ['action'=>'work_order_status_changed','auditable_id'=>2]);
    }

    public function test_admin_can_issue_registered_quote_and_manual_receipt(): void
    {
        $this->seed(DatabaseSeeder::class);
        $token = $this->postJson('/api/auth/login', ['email'=>'admin@apollo.com.br','password'=>'password'])->json('data.token');

        $this->withToken($token)->postJson('/api/documents', [
            'type' => 'quote',
            'customer_id' => 1,
            'address_id' => 1,
            'issued_at' => now()->toDateString(),
            'valid_until' => now()->addDays(10)->toDateString(),
            'discount' => 20,
            'items' => [['name'=>'Manutenção preventiva','quantity'=>2,'unit_price'=>200]],
        ])->assertCreated()
            ->assertJsonPath('data.type', 'quote')
            ->assertJsonPath('data.customer_name', 'Carlos Silva')
            ->assertJsonPath('data.total', '380.00');

        $this->withToken($token)->postJson('/api/documents', [
            'type' => 'receipt',
            'customer_name' => 'Cliente avulso',
            'customer_document' => '000.000.000-00',
            'issued_at' => now()->toDateString(),
            'payment_method' => 'pix',
            'items' => [['name'=>'Pagamento de limpeza','quantity'=>1,'unit_price'=>150]],
        ])->assertCreated()
            ->assertJsonPath('data.type', 'receipt')
            ->assertJsonPath('data.customer_name', 'Cliente avulso')
            ->assertJsonPath('data.total', '150.00');

        $this->assertDatabaseCount('business_documents', 2);
        $this->assertDatabaseCount('business_document_items', 2);
        $this->assertDatabaseHas('audit_logs', ['action'=>'business_document_created']);
    }

    public function test_admin_can_configure_company_branding(): void
    {
        Storage::fake('public');
        $this->seed(DatabaseSeeder::class);
        $token = $this->postJson('/api/auth/login', ['email'=>'admin@apollo.com.br','password'=>'password'])->json('data.token');

        $response = $this->withToken($token)->post('/api/settings', [
            'company_name' => 'Clima Norte',
            'legal_name' => 'Clima Norte Refrigeração Ltda.',
            'document' => '12.345.678/0001-90',
            'state_registration' => '123456789',
            'city' => 'São Luís',
            'state' => 'MA',
            'timezone' => 'America/Sao_Paulo',
            'document_accent_color' => '#15576A',
            'logo_image' => UploadedFile::fake()->image('logo.png', 300, 300),
            'document_header_image' => UploadedFile::fake()->image('cabecalho.jpg', 1400, 240),
            'document_footer_image' => UploadedFile::fake()->image('rodape.jpg', 1400, 160),
        ]);

        $response->assertOk()
            ->assertJsonPath('data.company_name', 'Clima Norte')
            ->assertJsonPath('data.document', '12.345.678/0001-90')
            ->assertJsonStructure(['data'=>['logo_image_url','document_header_image_url','document_footer_image_url']]);
        Storage::disk('public')->assertExists($response->json('data.logo_image'));
        Storage::disk('public')->assertExists($response->json('data.document_header_image'));
        Storage::disk('public')->assertExists($response->json('data.document_footer_image'));
    }
}
