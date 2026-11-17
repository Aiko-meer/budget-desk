<?php

namespace App\Livewire\Reports;

use App\Services\ReportService;
use Carbon\Carbon;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Reports')]
class Index extends Component
{
    public string $month = '';

    public function mount(): void
    {
        $this->month = now()->format('Y-m');
    }

    public function previousMonth(): void
    {
        $this->month = Carbon::createFromFormat('Y-m', $this->month)->subMonthNoOverflow()->format('Y-m');
    }

    public function nextMonth(): void
    {
        $this->month = Carbon::createFromFormat('Y-m', $this->month)->addMonthNoOverflow()->format('Y-m');
    }

    public function render()
    {
        $reports = app(ReportService::class);

        $summary = $reports->monthSummary($this->month);
        $categories = $reports->categoryBreakdown($this->month);
        $budgetRows = collect($reports->budgetRows($this->month));
        $series = $reports->monthlySeries(6);
        $savingsSeries = $reports->savingsBalanceSeries(6);

        $expenseTotal = max(0.0001, $summary['expenses']);
        $charts = [
            [
                'id' => 'reportCashflowChart',
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
                            'maxBarThickness' => 22,
                        ],
                        [
                            'label' => 'Spent',
                            'data' => array_column($series, 'expenses'),
                            'backgroundColor' => 'rgba(251, 113, 133, 0.78)',
                            'borderRadius' => 7,
                            'maxBarThickness' => 22,
                        ],
                        [
                            'label' => 'Saved',
                            'data' => array_column($series, 'saved'),
                            'backgroundColor' => 'rgba(34, 211, 238, 0.78)',
                            'borderRadius' => 7,
                            'maxBarThickness' => 22,
                        ],
                    ],
                ],
            ],
            [
                'id' => 'reportSavingsChart',
                'type' => 'line',
                'currency' => \App\Models\Settings::current()->currency_symbol,
                'data' => [
                    'labels' => array_column($savingsSeries, 'label'),
                    'datasets' => [[
                        'label' => 'Cumulative savings',
                        'data' => array_column($savingsSeries, 'total'),
                        'borderColor' => '#22d3ee',
                        'backgroundColor' => 'rgba(34, 211, 238, 0.14)',
                        'fill' => true,
                        'tension' => 0.38,
                        'pointRadius' => 3,
                        'pointBackgroundColor' => '#22d3ee',
                        'borderWidth' => 2.5,
                    ]],
                ],
            ],
        ];

        return view('livewire.reports.index', [
            'summary' => $summary,
            'categories' => $categories,
            'budgetRows' => $budgetRows,
            'series' => $series,
            'expenseTotal' => $expenseTotal,
            'monthLabel' => Carbon::createFromFormat('Y-m', $this->month)->format('F Y'),
            'charts' => $charts,
            'budgetTotal' => round($budgetRows->sum('budget'), 2),
            'budgetSpent' => round($budgetRows->sum('spent'), 2),
        ]);
    }
}
