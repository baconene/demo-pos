<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class PaymentTender extends Model {
    protected $fillable = ['name', 'is_active', 'display_order'];
    protected $casts = ['is_active' => 'boolean'];

    public function payments() { return $this->hasMany(Payment::class); }
    public function financialTransactions() { return $this->hasMany(FinancialTransaction::class); }
}
