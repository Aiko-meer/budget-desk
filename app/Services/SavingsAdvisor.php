<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\Payday;
use App\Models\Settings;
use App\Models\Transfer;
use App\Support\Money;
use App\Support\PayPeriod;
use Carbon\Carbon;

class SavingsAdvisor
{
    public function __construct(
        private Settings $settings,
        private PayPeriodService $periods,
    ) {
    }

    public function advise(Payday $payday): array
    {
        $period = $this->periods->periodForPayday($payday);
        $net = (float) $payday->net_amount;
        $rate = (float) $this->settings->savings_rate;
        $step = (int) $this->settings->savings_rounding;

        $rawTarget = round($net * $rate / 100, 2);
        $target = $step > 0 ? round($rawTarget / $step) * $step : $rawTarget;
        $billsDue = $this->billsDue($period);
        $safeToSpend = max(0.0, round($net - $billsDue - $target, 2));
        $days = max(1, $period->days());
        $daily = round($safeToSpend / $days, 2);
        $actual = $this->savedBetween($period->start, $period->end);
        $difference = round($actual - $target, 2);

        $status = 'met';
        if ($difference < -0.5) {
            $status = 'short';
        } elseif ($difference > 0.5) {
            $status = 'ahead';
        }

        $maxSave = max(0.0, round($net - $billsDue, 2));
        $shortfall = $maxSave < $target;

        return [
            'payday' => $payday,
            'period' => $period,
            'net' => $net,
            'rate' => $rate,
            'raw_target' => $rawTarget,
            'target' => round($target, 2),
            'bills_due' => $billsDue,
            'safe_to_spend' => $safeToSpend,
            'daily' => $daily,
            'actual_saved' => $actual,
            'difference' => $difference,
            'status' => $status,
            'max_save' => $maxSave,
            'shortfall' => $shortfall,
            'advice' => $this->adviceText($period, $net, $target, $billsDue, $safeToSpend, $days, $daily, $shortfall, $maxSave),
        ];
    }

    public function billsDue(PayPeriod $period): float
    {
        $total = 0.0;
        $cursor = $period->start->copy()->startOfMonth();
        $lastMonth = $period->end->copy()->startOfMonth();

        while ($cursor->lte($lastMonth)) {
            foreach (Bill::where('is_active', true)->get() as $bill) {
                $due = $bill->dueDateInMonth($cursor);

                if ($due->betweenIncluded($period->start, $period->end)) {
                    $total += (float) $bill->amount;
                }
            }

            $cursor->addMonthNoOverflow();
        }

        return round($total, 2);
    }

    public function savedBetween(Carbon $start, Carbon $end): float
    {
        $transfers = Transfer::with(['fromAccount', 'toAccount'])
            ->whereDate('transferred_on', '>=', $start->toDateString())
            ->whereDate('transferred_on', '<=', $end->toDateString())
            ->get();

        return round((float) $transfers->sum(function (Transfer $transfer) {
            if (! $transfer->fromAccount || ! $transfer->toAccount) {
                return 0;
            }

            if ($transfer->isContribution()) {
                return (float) $transfer->amount;
            }

            if ($transfer->fromAccount->counts_as_savings
                && ! $transfer->toAccount->counts_as_savings
                && $transfer->goal_id) {
                return -(float) $transfer->amount;
            }

            return 0;
        }), 2);
    }

    private function adviceText(
        PayPeriod $period,
        float $net,
        float $target,
        float $bills,
        float $safe,
        int $days,
        float $daily,
        bool $shortfall,
        float $maxSave,
    ): string {
        if ($shortfall) {
            return sprintf(
                'Bills leave only %s of this payday. Saving %s would overdraw it — save at most %s this period, or trim a bill.',
                Money::format($maxSave),
                Money::format($target),
                Money::format($maxSave),
            );
        }

        return sprintf(
            'Payday %s: set aside %s (%s%% of %s). Bills through %s: %s. That leaves %s for %d days — about %s/day.',
            $period->start->format('M j'),
            Money::format($target),
            rtrim(rtrim(number_format((float) $this->settings->savings_rate, 2, '.', ''), '0'), '.'),
            Money::format($net),
            $period->end->format('M j'),
            Money::format($bills),
            Money::format($safe),
            $days,
            Money::format($daily),
        );
    }
}
