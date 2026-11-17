<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Settings extends Model
{
    protected $table = 'settings';

    protected $guarded = [];

    protected $casts = [
        'savings_rate' => 'decimal:2',
        'savings_rounding' => 'integer',
        'emergency_fund_months' => 'integer',
    ];

    public static function current(): self
    {
        return app(self::class);
    }

    public static function defaults(): array
    {
        return [
            'currency_code' => 'PHP',
            'currency_symbol' => '₱',
            'savings_rate' => 20,
            'savings_rounding' => 100,
            'emergency_fund_months' => 3,
            'timezone' => 'Asia/Manila',
        ];
    }
}
