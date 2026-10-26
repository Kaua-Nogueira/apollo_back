<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    protected function prepareForValidation(): void
    {
        $whatsapp = $this->input('whatsapp');
        $phone = $this->input('phone');

        if ($this->has('document') && trim((string)$this->input('document')) === '') {
            $this->merge(['document' => null]);
        }

        if (empty($phone) && !empty($whatsapp)) {
            $this->merge(['phone' => $whatsapp]);
        } elseif (empty($whatsapp) && !empty($phone)) {
            $this->merge(['whatsapp' => $phone]);
        }
    }

    public function rules(): array
    {
        $id = $this->route('customer')?->id;
        return [
            'name' => 'required|string|max:180',
            'contact_name' => 'nullable|string|max:180',
            'document' => 'nullable|string|max:30|unique:customers,document,' . $id,
            'whatsapp' => 'nullable|string|max:30',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:180',
            'notes' => 'nullable|string',
            'active' => 'sometimes|boolean',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->isMethod('post') && empty($this->input('whatsapp')) && empty($this->input('phone'))) {
                $validator->errors()->add('whatsapp', 'O WhatsApp do cliente é obrigatório.');
            }
        });
    }
}
