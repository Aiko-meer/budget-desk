<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'counts_as_savings' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function paydays()
    {
        return $this->hasMany(Payday::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function transfersOut()
    {
        return $this->hasMany(Transfer::class, 'from_account_id');
    }

    public function transfersIn()
    {
        return $this->hasMany(Transfer::class, 'to_account_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            'e_wallet' => 'E-wallet',
            'checking' => 'Checking',
            'savings' => 'Savings',
            'investment' => 'Investment',
            default => 'Cash',
        };
    }
}
