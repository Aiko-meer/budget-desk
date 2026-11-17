<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'spent_on' => 'date',
        'amount' => 'decimal:2',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function bill()
    {
        return $this->belongsTo(Bill::class);
    }

    public function scopeForMonth($query, string $month)
    {
        [$year, $monthNumber] = array_map('intval', explode('-', $month));

        return $query->whereYear('spent_on', $year)->whereMonth('spent_on', $monthNumber);
    }

    public function scopeBetweenDates($query, $start, $end)
    {
        return $query->whereDate('spent_on', '>=', $start)->whereDate('spent_on', '<=', $end);
    }
}
