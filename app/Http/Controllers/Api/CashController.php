<?php

namespace App\Http\Controllers\Api;

use App\Models\Expense;
use App\Models\Payment;
use Illuminate\Http\Request;

class CashController
{
    public function index(Request $request)
    {
        $period = $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
        ]);
        $from = $period['from'] ?? now()->startOfMonth()->toDateString();
        $to = $period['to'] ?? now()->toDateString();

        $payments = Payment::with(['workOrder.customer', 'workOrder.services'])
            ->whereDate('paid_at', '>=', $from)
            ->whereDate('paid_at', '<=', $to)
            ->get()
            ->map(function (Payment $payment) {
                $order = $payment->workOrder;
                $services = $order?->services->pluck('name')->filter()->unique()->implode(', ');

                return [
                'id' => 'payment-'.$payment->id,
                'type' => 'inflow',
                'description' => 'Pagamento da OS '.($order?->number ?? '#'.$payment->work_order_id).($services ? ' · '.$services : ''),
                'reference' => $order?->customer?->name,
                'amount' => (float) $payment->amount,
                'method' => $payment->method,
                'occurred_at' => $payment->paid_at,
                ];
            });

        $expenses = Expense::query()
            ->whereDate('paid_at', '>=', $from)
            ->whereDate('paid_at', '<=', $to)
            ->get()
            ->map(fn (Expense $expense) => [
                'id' => 'expense-'.$expense->id,
                'type' => 'outflow',
                'description' => $expense->description,
                'reference' => null,
                'amount' => (float) $expense->amount,
                'method' => $expense->method,
                'occurred_at' => $expense->paid_at,
            ]);

        $entries = $payments->concat($expenses)->sortByDesc('occurred_at')->values();
        $income = (float) $payments->sum('amount');
        $outflow = (float) $expenses->sum('amount');

        return response()->json(['data' => [
            'period' => compact('from', 'to'),
            'income' => $income,
            'expenses' => $outflow,
            'balance' => $income - $outflow,
            'entries' => $entries,
        ]]);
    }
}
