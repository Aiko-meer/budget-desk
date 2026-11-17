<?php

namespace App\Services;

use App\Models\Payday;
use App\Models\Settings;
use App\Support\PayPeriod;
use Carbon\Carbon;

class PayPeriodService
{
    public function __construct(private Settings $settings)
    {
    }

    /**
     * The next 15th or last day strictly after the given date.
     */
    public function nextScheduledDateAfter(Carbon $date): Carbon
    {
        $date = $date->copy()->startOfDay();
        $fifteenth = $date->copy()->startOfMonth()->addDays(14);
        $lastDay = $date->copy()->endOfMonth()->startOfDay();

        if ($date->lt($fifteenth)) {
            return $fifteenth;
        }

        if ($date->lt($lastDay)) {
            return $lastDay;
        }

        return $date->copy()->addMonthNoOverflow()->startOfMonth()->addDays(14);
    }

    public function isPayday(Carbon $date): bool
    {
        $date = $date->copy()->startOfDay();

        return $date->day === 15 || $date->isSameDay($date->copy()->endOfMonth());
    }

    public function latestPaydayOnOrBefore(Carbon $date): ?Payday
    {
        return Payday::query()
            ->whereDate('pay_date', '<=', $date->toDateString())
            ->orderByDesc('pay_date')
            ->first();
    }

    public function nextPaydayRecordAfter(Carbon $date): ?Payday
    {
        return Payday::query()
            ->whereDate('pay_date', '>', $date->toDateString())
            ->orderBy('pay_date')
            ->first();
    }

    public function nextPaydayDate(Carbon $from = null): Carbon
    {
        return $this->nextScheduledDateAfter($from ?? now());
    }

    public function periodForPayday(Payday $payday): PayPeriod
    {
        $start = $payday->pay_date->copy()->startOfDay();
        $next = $this->nextScheduledDateAfter($start);
        $end = $next->copy()->subDay()->startOfDay();

        return new PayPeriod($start, $end, $payday, $next);
    }

    public function periodForDate(Carbon $date): ?PayPeriod
    {
        $payday = $this->latestPaydayOnOrBefore($date);

        return $payday ? $this->periodForPayday($payday) : null;
    }

    public function currentPeriod(): ?PayPeriod
    {
        $payday = $this->latestPaydayOnOrBefore(now());

        return $payday ? $this->periodForPayday($payday) : null;
    }

    public function lastPeriods(int $count): array
    {
        $paydays = Payday::query()->orderByDesc('pay_date')->limit($count)->get();

        return $paydays->map(fn (Payday $payday) => $this->periodForPayday($payday))->all();
    }

    public function scheduledDatesForYear(int $year): array
    {
        $dates = [];

        for ($month = 1; $month <= 12; $month++) {
            $start = Carbon::create($year, $month, 1)->startOfDay();
            $dates[] = $start->copy()->addDays(14);
            $dates[] = $start->copy()->endOfMonth()->startOfDay();
        }

        return $dates;
    }
}
