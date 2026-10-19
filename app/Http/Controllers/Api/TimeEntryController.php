<?php

namespace App\Http\Controllers\Api;

use App\Models\Employee;
use App\Models\TimeEntry;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TimeEntryController
{
    private const NEXT = [
        'clock_in' => ['clock_out'],
        'clock_out' => ['clock_in'],
    ];

    private function getOrCreateEmployee(Request $request): Employee
    {
        $user = $request->user();
        if (!$user) {
            throw ValidationException::withMessages(['employee' => 'Usuário não autenticado.']);
        }

        $employee = $user->employee;
        if (!$employee) {
            $employee = Employee::create([
                'user_id' => $user->id,
                'phone' => null,
                'specialty' => $user->role === 'employee' ? 'Funcionário' : 'Técnico de campo',
                'active' => true,
            ]);
        }

        return $employee;
    }

    public function ponto(Request $request)
    {
        $employee = $this->getOrCreateEmployee($request);

        $selectedDate = $request->input('date', now()->toDateString());
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)) {
            $selectedDate = now()->toDateString();
        }

        $month = $request->input('month', substr($selectedDate, 0, 7));
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = substr($selectedDate, 0, 7);
        }

        // Entries for the requested month
        $monthEntries = TimeEntry::where('employee_id', $employee->id)
            ->where('work_date', 'like', "{$month}%")
            ->orderBy('occurred_at')
            ->get();

        // Unique dates with entries in this month
        $datesWithEntries = $monthEntries->map(function ($entry) {
            return is_string($entry->work_date) ? substr($entry->work_date, 0, 10) : $entry->work_date->format('Y-m-d');
        })->unique()->values()->all();

        // Entries for the selected day
        $dayEntries = TimeEntry::where('employee_id', $employee->id)
            ->whereDate('work_date', $selectedDate)
            ->orderBy('occurred_at')
            ->get();

        $lastOnDay = $dayEntries->last();
        $nextAction = 'clock_in';
        $nextActionLabel = 'Bater Entrada';
        $clockStatus = 'off';

        if ($lastOnDay) {
            if ($lastOnDay->type === 'clock_in') {
                $nextAction = 'clock_out';
                $nextActionLabel = 'Bater Saída';
                $clockStatus = 'working';
            } elseif ($lastOnDay->type === 'clock_out') {
                $nextAction = 'clock_in';
                $nextActionLabel = 'Bater Entrada';
                $clockStatus = 'completed';
            }
        }

        // Previous attendance history (recent days)
        $allRecent = TimeEntry::where('employee_id', $employee->id)
            ->orderByDesc('work_date')
            ->orderBy('occurred_at')
            ->get()
            ->groupBy(function ($entry) {
                return is_string($entry->work_date) ? substr($entry->work_date, 0, 10) : $entry->work_date->format('Y-m-d');
            });

        $recentHistory = $allRecent->take(20)->map(function ($entries, $date) {
            $first = $entries->first();
            $last = $entries->last();
            $firstTime = $first ? Carbon::parse($first->occurred_at)->format('H:i') : null;
            $lastTime = ($entries->count() > 1 && $last) ? Carbon::parse($last->occurred_at)->format('H:i') : null;

            return [
                'date' => $date,
                'entries_count' => $entries->count(),
                'first_time' => $firstTime,
                'last_time' => $lastTime,
                'last_type' => $last?->type,
                'entries' => $entries->map(fn($e) => [
                    'id' => $e->id,
                    'type' => $e->type,
                    'time' => Carbon::parse($e->occurred_at)->format('H:i'),
                    'occurred_at' => (string) $e->occurred_at,
                    'notes' => $e->notes,
                ]),
            ];
        })->values();

        return response()->json([
            'data' => [
                'selected_date' => $selectedDate,
                'month' => $month,
                'day_entries' => $dayEntries->map(fn($e) => [
                    'id' => $e->id,
                    'type' => $e->type,
                    'time' => Carbon::parse($e->occurred_at)->format('H:i'),
                    'occurred_at' => (string) $e->occurred_at,
                    'notes' => $e->notes,
                ]),
                'dates_with_entries' => $datesWithEntries,
                'clock_status' => $clockStatus,
                'next_action' => $nextAction,
                'next_action_label' => $nextActionLabel,
                'recent_history' => $recentHistory,
                'month_stats' => [
                    'days_worked' => count($datesWithEntries),
                    'total_entries' => $monthEntries->count(),
                ],
                'employee' => [
                    'id' => $employee->id,
                    'name' => $employee->name,
                    'specialty' => $employee->specialty,
                ],
            ],
        ]);
    }

    public function clock(Request $request)
    {
        $employee = $this->getOrCreateEmployee($request);

        $date = $request->input('date', now()->toDateString());
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = now()->toDateString();
        }

        $last = TimeEntry::where('employee_id', $employee->id)
            ->whereDate('work_date', $date)
            ->latest('occurred_at')
            ->first();

        $action = $request->input('action');
        if (!$action || $action === 'auto') {
            if (!$last || $last->type === 'clock_out') {
                $action = 'clock_in';
            } else {
                $action = 'clock_out';
            }
        } else {
            if (!in_array($action, ['clock_in', 'clock_out'], true)) {
                throw ValidationException::withMessages(['action' => 'Ação de ponto inválida (apenas entrada e saída são permitidas).']);
            }
        }

        $time = $request->input('time');
        if ($date === now()->toDateString() && !$time) {
            $occurredAt = now();
        } elseif ($time && preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', $time)) {
            $occurredAt = Carbon::parse("{$date} {$time}");
        } else {
            $occurredAt = Carbon::parse("{$date} " . now()->format('H:i:s'));
        }

        $entry = TimeEntry::create([
            'employee_id' => $employee->id,
            'work_date' => $date,
            'type' => $action,
            'occurred_at' => $occurredAt,
            'notes' => $request->input('notes'),
        ]);

        return response()->json([
            'data' => $entry,
            'message' => 'Ponto registrado com sucesso!',
        ], 201);
    }
}

