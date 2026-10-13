<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model {
    protected $fillable = [
        'order_id', 'user_id', 'amount', 'method',
        'payment_tender_id', 'status', 'reference', 'notes',
    ];
    protected $casts = ['amount' => 'decimal:2'];

    public function order() { return $this->belongsTo(Order::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function tender() { return $this->belongsTo(PaymentTender::class, 'payment_tender_id'); }
    public function refunds() { return $this->hasMany(Refund::class); }
    public function financialTransactions() { return $this->hasMany(FinancialTransaction::class); }
}
