<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\PayrollRecord;
use Inertia\Inertia;
use Inertia\Response;

class HrisPageController extends Controller
{
    public function index(): Response
    {
        $employees = Employee::orderBy('name')
            ->get()
            ->map(fn ($e) => [
                'id'              => $e->id,
                'name'            => $e->name,
                'position'        => $e->position,
                'employment_type' => $e->employment_type,
                'salary_type'     => $e->salary_type,
                'base_rate'       => (float) $e->base_rate,
                'is_active'       => (bool) $e->is_active,
                'hired_at'        => $e->hired_at?->toDateString(),
                'notes'           => $e->notes,
            ]);

        $payrollRecords = PayrollRecord::with('employee')
            ->orderByDesc('period_end')
            ->orderByDesc('id')
            ->limit(100)
            ->get()
            ->map(fn ($r) => [
                'id'              => $r->id,
                'employee_id'     => $r->employee_id,
                'employee_name'   => $r->employee?->name,
                'period_start'    => $r->period_start?->toDateString(),
                'period_end'      => $r->period_end?->toDateString(),
                'days_worked'     => (float) $r->days_worked,
                'gross_pay'       => (float) $r->gross_pay,
                'deductions'      => (float) $r->deductions,
                'net_pay'         => (float) $r->net_pay,
                'status'          => $r->status,
                'notes'           => $r->notes,
                'paid_at'         => $r->paid_at?->toDateTimeString(),
            ]);

        return Inertia::render('HrisPage', [
            'employees'      => $employees,
            'payrollRecords' => $payrollRecords,
        ]);
    }
}
