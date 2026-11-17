<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SavingsGoal extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'target_amount' => 'decimal:2',
        'target_date' => 'date',
        'is_emergency_fund' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function transfers()
    {
        return $this->hasMany(Transfer::class, 'goal_id');
    }

    public function progressAmount(): float
    {
        $transfers = $this->transfers()->with(['fromAccount', 'toAccount'])->get();

        return round((float) $transfers->sum(function (Transfer $transfer) {
            if (! $transfer->toAccount || ! $transfer->fromAccount) {
                return 0;
            }

            if ($transfer->toAccount->counts_as_savings && ! $transfer->fromAccount->counts_as_savings) {
                return (float) $transfer->amount;
            }

            if ($transfer->fromAccount->counts_as_savings && ! $transfer->toAccount->counts_as_savings) {
                return -(float) $transfer->amount;
            }

            return 0;
        }), 2);
    }

    public function effectiveTarget(): float
    {
        if ($this->target_amount !== null && (float) $this->target_amount > 0) {
            return (float) $this->target_amount;
        }

        if ($this->is_emergency_fund) {
            $settings = Settings::current();
            $start = now()->startOfMonth()->subMonthsNoOverflow(3)->startOfDay();
            $end = now()->startOfMonth()->subSecond();

            $spent = (float) Expense::whereBetween('spent_on', [$start, $end])->sum('amount');

            if ($spent > 0) {
                return round(($spent / 3) * (int) $settings->emergency_fund_months, 2);
            }

            $budgeted = (float) Budget::where('month', now()->format('Y-m'))->sum('amount');

            if ($budgeted > 0) {
                return round($budgeted * (int) $settings->emergency_fund_months, 2);
            }
        }

        return 0;
    }

    public function progressPercent(): float
    {
        $target = $this->effectiveTarget();

        if ($target <= 0) {
            return 0;
        }

        return min(100, round(($this->progressAmount() / $target) * 100, 1));
    }

    public function targetSourceLabel(): string
    {
        if ($this->target_amount !== null && (float) $this->target_amount > 0) {
            return 'your target';
        }

        if ($this->is_emergency_fund) {
            return '3 × recent monthly expenses';
        }

        return 'no target yet';
    }

    public function formattedTarget(): string
    {
        return Money::format($this->effectiveTarget());
    }
}
