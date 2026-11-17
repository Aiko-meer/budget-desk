<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bill extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_active' => 'boolean',
        'due_day' => 'integer',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function dueDateInMonth(Carbon $month): Carbon
    {
        $day = min($this->due_day, $month->copy()->endOfMonth()->day);

        return $month->copy()->startOfMonth()->addDays($day - 1)->startOfDay();
    }

    public function isPaidForMonth(Carbon $month): bool
    {
        return $this->expenses()
            ->whereYear('spent_on', $month->year)
            ->whereMonth('spent_on', $month->month)
            ->exists();
    }

    public function nextDueDate(Carbon $from = null): Carbon
    {
        $from = ($from ?? now())->copy()->startOfDay();
        $due = $this->dueDateInMonth($from);

        if ($due->lt($from)) {
            $due = $this->dueDateInMonth($from->copy()->addMonthNoOverflow());
        }

        return $due;
    }
}
