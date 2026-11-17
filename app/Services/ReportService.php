<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\Category;
use App\Models\Expense;
use App\Models\Payday;
use App\Models\Transfer;
use Carbon\Carbon;

class ReportService
{
    public function __construct(private SavingsAdvisor $advisor)
    {
    }

    public function monthSummary(string $month): array
    {
        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $income = (float) Payday::whereBetween('pay_date', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])->sum('net_amount');
        $expenses = (float) Expense::whereBetween('spent_on', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])->sum('amount');
        $saved = $this->advisor->savedBetween($start, $end);

        return [
            'month' => $month,
            'income' => round($income, 2),
            'expenses' => round($expenses, 2),
            'saved' => round($saved, 2),
            'net' => round($income - $expenses - $saved, 2),
            'start' => $start,
            'end' => $end,
        ];
    }

    public function categoryBreakdown(string $month): array
    {
        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();

        return Expense::with('category')
            ->whereYear('spent_on', $start->year)
            ->whereMonth('spent_on', $start->month)
            ->get()
            ->groupBy('category_id')
            ->map(function ($expenses) {
                return [
                    'name' => $expenses->first()->category?->name ?? 'Uncategorized',
                    'color' => $expenses->first()->category?->color ?? '#64748b',
                    'total' => round((float) $expenses->sum('amount'), 2),
                ];
            })
            ->sortByDesc('total')
            ->values()
            ->all();
    }

    public function budgetRows(string $month): array
    {
        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();

        $spent = Expense::query()
            ->whereYear('spent_on', $start->year)
            ->whereMonth('spent_on', $start->month)
            ->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->pluck('total', 'category_id');

        $budgets = Budget::where('month', $month)->pluck('amount', 'category_id');

        return Category::active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (Category $category) => [
                'category' => $category,
                'budget' => (float) ($budgets[$category->id] ?? 0),
                'spent' => (float) ($spent[$category->id] ?? 0),
            ])
            ->all();
    }

    public function monthlySeries(int $months = 6, Carbon $end = null): array
    {
        $end = ($end ?? now())->copy()->startOfMonth();
        $series = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $month = $end->copy()->subMonthsNoOverflow($i);
            $summary = $this->monthSummary($month->format('Y-m'));
            $series[] = [
                'label' => $month->format('M'),
                'month' => $month->format('Y-m'),
                'income' => $summary['income'],
                'expenses' => $summary['expenses'],
                'saved' => $summary['saved'],
            ];
        }

        return $series;
    }

    public function savingsBalanceSeries(int $months = 6): array
    {
        $series = $this->monthlySeries($months);
        $cumulative = 0.0;

        return array_map(function (array $row) use (&$cumulative) {
            $cumulative += $row['saved'];

            return [
                'label' => $row['label'],
                'total' => round($cumulative, 2),
            ];
        }, $series);
    }

    public function recentTransactions(int $limit = 8): array
    {
        $rows = collect();

        foreach (Expense::with(['category', 'account'])->latest('spent_on')->limit($limit)->get() as $expense) {
            $rows->push([
                'date' => $expense->spent_on->copy(),
                'kind' => 'expense',
                'label' => $expense->note ?: ($expense->category?->name ?? 'Expense'),
                'meta' => $expense->category?->name ?? 'Expense',
                'amount' => -(float) $expense->amount,
            ]);
        }

        foreach (Transfer::with(['fromAccount', 'toAccount'])->latest('transferred_on')->limit($limit)->get() as $transfer) {
            $isContribution = $transfer->isContribution();
            $rows->push([
                'date' => $transfer->transferred_on->copy(),
                'kind' => 'transfer',
                'label' => $transfer->note ?: ($isContribution ? 'Saved to '.$transfer->toAccount?->name : 'Transfer to '.$transfer->toAccount?->name),
                'meta' => ($transfer->fromAccount?->name ?? '—').' → '.($transfer->toAccount?->name ?? '—'),
                'amount' => $isContribution ? 0.0 : -(float) $transfer->amount,
                'movement' => (float) $transfer->amount,
            ]);
        }

        foreach (Payday::with('account')->latest('pay_date')->limit($limit)->get() as $payday) {
            $rows->push([
                'date' => $payday->pay_date->copy(),
                'kind' => 'income',
                'label' => 'Salary',
                'meta' => $payday->account?->name ?? 'Income',
                'amount' => (float) $payday->net_amount,
            ]);
        }

        return $rows->sortByDesc(fn (array $row) => $row['date']->timestamp)->take($limit)->values()->all();
    }
}
