<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payday extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'pay_date' => 'date',
        'gross_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
