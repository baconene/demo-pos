<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    protected $fillable = [
        'name', 'position', 'employment_type', 'salary_type',
        'base_rate', 'is_active', 'hired_at', 'notes',
    ];

    protected $casts = [
        'base_rate' => 'decimal:2',
        'is_active' => 'boolean',
        'hired_at'  => 'date',
    ];

    public function payrollRecords(): HasMany
    {
        return $this->hasMany(PayrollRecord::class);
    }
}
