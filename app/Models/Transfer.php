<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transfer extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'transferred_on' => 'date',
        'amount' => 'decimal:2',
    ];

    public function fromAccount()
    {
        return $this->belongsTo(Account::class, 'from_account_id');
    }

    public function toAccount()
    {
        return $this->belongsTo(Account::class, 'to_account_id');
    }

    public function goal()
    {
        return $this->belongsTo(SavingsGoal::class, 'goal_id');
    }

    public function isContribution(): bool
    {
        return (bool) $this->toAccount?->counts_as_savings && ! (bool) $this->fromAccount?->counts_as_savings;
    }
}
