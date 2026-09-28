<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class CustomerRequest extends FormRequest { public function authorize(): bool{return $this->user()?->role==='admin';} public function rules(): array { $id=$this->route('customer')?->id;return ['name'=>'required|string|max:180','document'=>'nullable|string|max:30|unique:customers,document,'.$id,'phone'=>'required|string|max:30','whatsapp'=>'nullable|string|max:30','email'=>'nullable|email|max:180','notes'=>'nullable|string','active'=>'sometimes|boolean']; } }
