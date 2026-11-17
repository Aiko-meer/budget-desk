<?php

namespace App\Support;

use App\Models\Settings;

class Money
{
    public static function format(float|int|string|null $amount, bool $withSymbol = true, int $decimals = 2): string
    {
        $settings = Settings::current();
        $value = (float) $amount;
        $sign = $value < 0 ? '−' : '';
        $formatted = number_format(abs($value), $decimals, '.', ',');

        return $sign.($withSymbol ? $settings->currency_symbol : '').$formatted;
    }

    public static function compact(float|int|string|null $amount): string
    {
        $value = (float) $amount;
        $symbol = Settings::current()->currency_symbol;
        $sign = $value < 0 ? '−' : '';

        if (abs($value) >= 1000000) {
            return $sign.$symbol.number_format(abs($value) / 1000000, 1).'M';
        }

        if (abs($value) >= 10000) {
            return $sign.$symbol.number_format(abs($value) / 1000, 1).'k';
        }

        return $sign.$symbol.number_format(abs($value), 0);
    }
}
