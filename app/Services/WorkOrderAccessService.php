<?php

namespace App\Services;

use App\Models\User;
use App\Models\WorkOrder;

class WorkOrderAccessService
{
    public function ensureCanOperate(User $user, WorkOrder $order): void
    {
        if ($user->role === 'technician' && $order->technician_id !== $user->employee?->id) {
            abort(403, 'Esta ordem não está atribuída a você.');
        }
    }
}
