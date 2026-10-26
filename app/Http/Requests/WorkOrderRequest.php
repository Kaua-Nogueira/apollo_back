<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WorkOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'customer_id' => 'required|exists:customers,id',
            'address_id' => 'required|exists:customer_addresses,id',
            'equipment_id' => 'nullable|exists:customer_equipment,id',
            'technician_id' => 'nullable|exists:employees,id',
            'appointment_id' => 'nullable|exists:appointments,id',
            'scheduled_at' => 'required|date',
            'problem_reported' => 'nullable|string',
            'notes' => 'nullable|string',
            'services' => 'array',
            'services.*.service_id' => 'nullable|exists:services,id',
            'services.*.name' => 'nullable|string|max:120',
            'services.*.quantity' => 'required|numeric|min:.01',
            'services.*.unit_price' => 'required|numeric|min:0',
            'products' => 'array',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|numeric|min:.001',
            'products.*.unit_price' => 'required|numeric|min:0',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $services = $this->input('services', []);
            if (is_array($services)) {
                foreach ($services as $index => $item) {
                    if (empty($item['service_id']) && empty(trim($item['name'] ?? ''))) {
                        $validator->errors()->add("services.{$index}.service_id", "Selecione um serviço cadastrado ou informe o nome do novo serviço.");
                    }
                }
            }
        });
    }
}

