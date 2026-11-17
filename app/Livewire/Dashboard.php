<?php

namespace App\Livewire;

use App\Models\Account;
use App\Models\Bill;
use App\Models\SavingsGoal;
use App\Services\BalanceService;
use App\Services\PayPeriodService;
use App\Services\ReportService;
use App\Services\SavingsAdvisor;
use App\Support\Money;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Dashboard')]
class Dashboard extends Component
{
    #[On('expense-saved')]
    #[On('transfer-saved')]
    #[On('payday-saved')]
    public function refreshDashboard(): void
    {
        // Re-render with fresh numbers.
    }

    public function render()
    {
        $periods = app(PayPeriodService::class);
        $advisor = app(SavingsAdvisor::class);
        $reports = app(ReportService::class);
        $balanceService = app(BalanceService::class);

        $currentPeriod = $periods->currentPeriod();
        $advice = $currentPeriod?->payday ? $advisor->advise($currentPeriod->payday) : null;
        $nextPayday = $periods->nextPaydayDate();
        $daysToPayday = (int) now()->startOfDay()->diffInDays($nextPayday->copy()->startOfDay(), false);

        $accounts = Account::query()->where('is_active', true)->orderBy('sort_order')->get();
        $balanceMap = $balanceService->all();
        $totalCash = $balanceService->totalCash();
        $totalSavings = $balanceService->totalSavings();

        $month = now()->format('Y-m');
        $summary = $reports->monthSummary($month);
        $budgetRows = collect($reports->budgetRows($month));
        $budgetTotal = round($budgetRows->sum('budget'), 2);
        $spentTotal = round($budgetRows->sum('spent'), 2);
        $budgetPercent = $budgetTotal > 0 ? min(100, round(($spentTotal / $budgetTotal) * 100, 1)) : 0;

        $topCategories = array_slice($reports->categoryBreakdown($month), 0, 4);

        $upcomingBills = Bill::query()
            ->where('is_active', true)
            ->with('category')
            ->get()
            ->map(function (Bill $bill) {
                $due = $bill->nextDueDate();

                return [
                    'bill' => $bill,
                    'due' => $due,
                    'days' => (int) now()->startOfDay()->diffInDays($due, false),
                    'paid' => $bill->isPaidForMonth($due),
                ];
            })
            ->sortBy('due')
            ->take(5)
            ->values();

        $emergencyGoal = SavingsGoal::query()
            ->where('is_active', true)
            ->where('is_emergency_fund', true)
            ->first();

        $activeGoals = SavingsGoal::query()->where('is_active', true)->get();
        $recent = $reports->recentTransactions(7);
        $series = $reports->monthlySeries(6);
        $categoryData = array_slice($reports->categoryBreakdown($month), 0, 6);

        $palette = ['#34d399', '#22d3ee', '#a78bfa', '#fbbf24', '#fb7185', '#60a5fa'];

        $charts = [
            [
                'id' => 'cashflowChart',
                'type' => 'bar',
                'currency' => \App\Models\Settings::current()->currency_symbol,
                'data' => [
                    'labels' => array_column($series, 'label'),
                    'datasets' => [
                        [
                            'label' => 'Income',
                            'data' => array_column($series, 'income'),
                            'backgroundColor' => 'rgba(52, 211, 153, 0.85)',
                            'borderRadius' => 7,
                            'maxBarThickness' => 20,
                        ],
                        [
                            'label' => 'Spent',
                            'data' => array_column($series, 'expenses'),
                            'backgroundColor' => 'rgba(251, 113, 133, 0.78)',
                            'borderRadius' => 7,
                            'maxBarThickness' => 20,
                        ],
                        [
                            'label' => 'Saved',
                            'data' => array_column($series, 'saved'),
                            'backgroundColor' => 'rgba(34, 211, 238, 0.78)',
                            'borderRadius' => 7,
                            'maxBarThickness' => 20,
                        ],
                    ],
                ],
            ],
            [
                'id' => 'categoryChart',
                'type' => 'doughnut',
                'currency' => \App\Models\Settings::current()->currency_symbol,
                'legend' => false,
                'data' => [
                    'labels' => array_column($categoryData, 'name'),
                    'datasets' => [[
                        'data' => array_column($categoryData, 'total'),
                        'backgroundColor' => collect($categoryData)->map(fn ($row, $index) => $row['color'] ?: $palette[$index % count($palette)])->all(),
                        'borderWidth' => 0,
                        'hoverOffset' => 8,
                    ]],
                ],
                'options' => [
                    'cutout' => '70%',
                ],
            ],
        ];

        $greeting = match (true) {
            now()->hour < 12 => 'Good morning',
            now()->hour < 18 => 'Good afternoon',
            default => 'Good evening',
        };

        return view('livewire.dashboard', compact(
            'currentPeriod',
            'advice',
            'nextPayday',
            'daysToPayday',
            'accounts',
            'balanceMap',
            'totalCash',
            'totalSavings',
            'summary',
            'budgetTotal',
            'spentTotal',
            'budgetPercent',
            'topCategories',
            'upcomingBills',
            'emergencyGoal',
            'activeGoals',
            'recent',
            'series',
            'charts',
            'greeting',
        ));
    }
}
