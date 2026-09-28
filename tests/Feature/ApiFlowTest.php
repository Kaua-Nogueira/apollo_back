<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Employee;
use App\Models\User;
use App\Models\WorkOrder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApiFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_login_read_dashboard_and_create_customer(): void
    {
        $token = $this->adminToken();

        $this->withToken($token)->getJson('/api/dashboard')->assertOk()->assertJsonStructure(['data'=>['metrics','upcoming']]);
        $this->withToken($token)->postJson('/api/customers', ['name'=>'Novo Cliente','phone'=>'(98) 99999-9999'])
            ->assertCreated()->assertJsonPath('data.name', 'Novo Cliente');
    }

    public function test_work_order_transition_is_persisted_in_history(): void
    {
        $token = $this->adminToken();
        $order = $this->workOrder();

        $this->withToken($token)->postJson("/api/work-orders/{$order->id}/transition", ['action'=>'complete'])
            ->assertOk()->assertJsonPath('data.status', 'completed');
        $this->assertDatabaseHas('work_order_status_history', ['work_order_id'=>$order->id,'to_status'=>'completed']);
        $this->assertDatabaseHas('audit_logs', ['action'=>'work_order_status_changed','auditable_id'=>$order->id]);
    }

    public function test_admin_can_issue_registered_quote_and_manual_receipt(): void
    {
        $token = $this->adminToken();
        [$customer, $address] = $this->customerContext();

        $this->withToken($token)->postJson('/api/documents', [
            'type' => 'quote',
            'customer_id' => $customer->id,
            'address_id' => $address->id,
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
        $token = $this->adminToken();

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

    private function adminToken(): string
    {
        $this->seed(UserSeeder::class);

        return $this->postJson('/api/auth/login', [
            'email' => 'admin@apollo.com.br',
            'password' => 'password',
        ])->assertOk()
            ->assertJsonPath('data.user.role', 'admin')
            ->json('data.token');
    }

    private function customerContext(): array
    {
        $customer = Customer::create([
            'name' => 'Carlos Silva',
            'phone' => '(98) 98811-2233',
            'email' => 'carlos@email.com',
        ]);

        $address = $customer->addresses()->create([
            'label' => 'Residência',
            'street' => 'Rua das Acácias',
            'number' => '120',
            'district' => 'Cohama',
            'city' => 'São Luís',
            'state' => 'MA',
        ]);

        return [$customer, $address];
    }

    private function workOrder(): WorkOrder
    {
        [$customer, $address] = $this->customerContext();
        $technician = Employee::create([
            'user_id' => User::where('email', 'joao@apollo.com.br')->firstOrFail()->id,
            'phone' => '(98) 99123-4500',
            'specialty' => 'Manutenção e limpeza',
            'hired_at' => '2024-02-05',
        ]);

        return WorkOrder::create([
            'number' => 'OS-TESTE-0001',
            'customer_id' => $customer->id,
            'address_id' => $address->id,
            'technician_id' => $technician->id,
            'scheduled_at' => now(),
            'problem_reported' => 'Equipamento não está refrigerando.',
            'status' => 'in_service',
        ]);
    }
}
