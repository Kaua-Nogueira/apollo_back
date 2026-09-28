<?php
namespace App\Http\Controllers\Api;
use App\Models\Employee;
class EmployeeController { public function index(){return Employee::with('user')->where('active',true)->orderBy('id')->paginate(100);} }
