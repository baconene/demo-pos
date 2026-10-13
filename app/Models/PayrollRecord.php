<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollRecord extends Model
{
    protected $fillable = [
        'employee_id', 'period_start', 'period_end', 'days_worked',
        'gross_pay', 'deductions', 'net_pay', 'status', 'notes',
        'paid_at', 'financial_transaction_id',
    ];

    protected $casts = [
        'period_start'  => 'date',
        'period_end'    => 'date',
        'days_worked'   => 'decimal:2',
        'gross_pay'     => 'decimal:2',
        'deductions'    => 'decimal:2',
        'net_pay'       => 'decimal:2',
        'paid_at'       => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function financialTransaction(): BelongsTo
    {
        return $this->belongsTo(FinancialTransaction::class);
    }
}
