<?php

namespace App\Http\Controllers\Api;

use App\Models\Expense;
use App\Services\AuditService;
use Illuminate\Http\Request;

class ExpenseController
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
        ]);

        return Expense::query()
            ->when($data['from'] ?? null, fn ($query, $date) => $query->whereDate('paid_at', '>=', $date))
            ->when($data['to'] ?? null, fn ($query, $date) => $query->whereDate('paid_at', '<=', $date))
            ->orderByDesc('paid_at')
            ->get();
    }

    public function store(Request $request, AuditService $audit)
    {
        $data = $request->validate([
            'description' => 'required|string|max:180',
            'amount' => 'required|numeric|min:0.01',
            'method' => 'required|in:pix,cash,credit_card,debit_card,transfer,bank_slip,other',
            'paid_at' => 'required|date',
        ]);

        $expense = Expense::create([...$data, 'created_by' => $request->user()->id]);
        $audit->record('expense_created', $expense, [], $expense->toArray());

        return response()->json(['data' => $expense], 201);
    }
}
