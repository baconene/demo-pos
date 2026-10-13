<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QueueNumber extends Model
{
    protected $fillable = ['number', 'status', 'called_at', 'completed_at'];

    protected $casts = [
        'called_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
